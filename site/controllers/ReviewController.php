<?php
// site/controllers/ReviewController.php

class ReviewController extends Controller
{
    public function create(): void
    {
        $this->requireLogin();
        csrfCheck();

        $coworkingId = Request::int('coworking_id');
        $rating      = max(1, min(5, Request::int('rating', 5)));
        $comment     = trim((string) Request::post('comment', ''));

        if ($coworkingId <= 0 || $comment === '') {
            flash('err', 'Будь ласка, заповніть коментар');
            Response::back();
            return;
        }

        $bm = new BookingModel();
        if (!$bm->userHasBookingInCoworking(Auth::id(), $coworkingId)) {
            flash('err', 'Залишати відгук можуть лише користувачі, які мали бронювання тут');
            Response::redirect(siteUrl('coworking', ['id' => $coworkingId]));
            return;
        }

        $rm = new ReviewModel();
        if ($rm->userHasReviewedCoworking(Auth::id(), $coworkingId)) {
            flash('warn', 'Ви вже залишали відгук для цього коворкінгу');
            Response::redirect(siteUrl('coworking', ['id' => $coworkingId]));
            return;
        }

        try {
            $rm->create(Auth::id(), $coworkingId, $rating, $comment);
            flash('ok', 'Дякуємо за відгук!');
        } catch (Throwable $e) {
            flash('err', 'Не вдалось зберегти відгук: ' . $e->getMessage());
        }

        Response::redirect(siteUrl('coworking', ['id' => $coworkingId]));
    }
}
