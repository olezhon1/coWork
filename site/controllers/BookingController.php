<?php
// site/controllers/BookingController.php

class BookingController extends Controller
{
    public function form(): void
    {
        $this->requireLogin();

        $workspaceId = Request::int('workspace_id');
        if ($workspaceId <= 0) { Response::notFound('Робоче місце не знайдено'); return; }

        $workspace = (new WorkspaceModel())->findById($workspaceId);
        if (!$workspace) { Response::notFound('Робоче місце не знайдено'); return; }

        $cw = (new CoworkingModel())->findById((int) $workspace['coworking_id']);
        $hours = (new OperatingHoursModel())->forCoworking((int) $workspace['coworking_id']);

        // Заброньовані слоти на найближчий тиждень (для візуального виключення)
        $dayStart = date('Y-m-d 00:00:00');
        $dayEnd   = date('Y-m-d 23:59:59', strtotime('+7 days'));
        $booked   = (new BookingSlotModel())->bookedSlotsForWorkspace($workspaceId, $dayStart, $dayEnd);

        $this->render('booking/form', [
            'title'     => 'Бронювання — ' . $workspace['name'],
            'workspace' => $workspace,
            'cw'        => $cw,
            'hours'     => $hours,
            'booked'    => $booked,
            'errors'    => [],
            'values'    => [
                'start_time' => date('Y-m-d') . 'T10:00',
                'end_time'   => date('Y-m-d') . 'T12:00',
            ],
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        csrfCheck();

        $workspaceId = Request::int('workspace_id');
        $start = trim((string) Request::post('start_time', ''));
        $end   = trim((string) Request::post('end_time', ''));

        $wm = new WorkspaceModel();
        $workspace = $wm->findById($workspaceId);
        if (!$workspace) { Response::notFound('Робоче місце не знайдено'); return; }

        // Конвертуємо datetime-local (2025-04-21T10:00) -> SQL format
        $startTs = strtotime(str_replace('T', ' ', $start));
        $endTs   = strtotime(str_replace('T', ' ', $end));

        $errors = [];
        if (!$startTs || !$endTs)            $errors['time'] = 'Вкажіть час початку та кінця';
        elseif ($startTs >= $endTs)          $errors['time'] = 'Кінець повинен бути пізніше початку';
        elseif ($startTs < time() - 300)     $errors['time'] = 'Не можна бронювати в минулому';

        $startSql = $startTs ? date('Y-m-d H:i:s', $startTs) : null;
        $endSql   = $endTs   ? date('Y-m-d H:i:s', $endTs)   : null;

        if (!$errors) {
            $oh = new OperatingHoursModel();
            $coworkingId = (int) $workspace['coworking_id'];
            $is247 = !empty($workspace['is_24_7']);
            if (!$oh->intervalWithinHours($coworkingId, $is247, $startSql, $endSql)) {
                $errors['time'] = 'Обраний інтервал виходить за межі робочих годин коворкінгу';
            }
        }

        if (!$errors) {
            $slotModel = new BookingSlotModel();
            if ($slotModel->hasConflict($workspaceId, $startSql, $endSql)) {
                $errors['time'] = 'Цей час вже заброньовано. Оберіть інший інтервал';
            }
        }

        if ($errors) {
            $cw = (new CoworkingModel())->findById((int) $workspace['coworking_id']);
            $hours = (new OperatingHoursModel())->forCoworking((int) $workspace['coworking_id']);
            $this->render('booking/form', [
                'title'     => 'Бронювання — ' . $workspace['name'],
                'workspace' => $workspace,
                'cw'        => $cw,
                'hours'     => $hours,
                'booked'    => [],
                'errors'    => $errors,
                'values'    => ['start_time' => $start, 'end_time' => $end],
            ]);
            return;
        }

        $hours = ($endTs - $startTs) / 3600.0;
        $totalPrice = round($hours * (float) $workspace['price_per_hour'], 2);

        $bm = new BookingModel();
        $slotModel = new BookingSlotModel();
        $bm->beginTransaction();
        try {
            $bookingId = $bm->create(Auth::id(), $workspaceId, BookingStatus::Pending->value, $totalPrice);
            $slotModel->create($bookingId, $startSql, $endSql);
            $bm->commit();
        } catch (Throwable $e) {
            $bm->rollBack();
            flash('err', 'Не вдалось створити бронювання: ' . $e->getMessage());
            Response::redirect(siteUrl('book', ['workspace_id' => $workspaceId]));
            return;
        }

        (new AuditModel())->log(
            Auth::id(),
            (string) ($_SESSION['user_name'] ?? ''),
            'INSERT',
            'bookings',
            $bookingId,
            "Створено бронювання #{$bookingId} ({$startSql} — {$endSql}, {$totalPrice} ₴)",
        );

        flash('ok', 'Бронювання створено. Статус: очікує підтвердження.');
        Response::redirect(siteUrl('profile'));
    }
}
