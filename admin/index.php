<?php
// index.php — головний роутер адмінки

require_once __DIR__ . '/config/bootstrap.php';
requireAdmin();

$currentRole = currentAdminRole() ?? UserRole::User;

// ── Параметри ─────────────────────────────────────────────────────────────────
$tableRaw = $_GET['t'] ?? '';
$action   = $_GET['a'] ?? 'list';
$id       = (int) ($_GET['id'] ?? 0);
$page     = max(1, (int) ($_GET['p'] ?? 1));
$perPage  = 20;

// Фільтри (GET)
$filters = [];
foreach (['search','city','status','role','type_key','coworking_id','workspace_id',
             'feature_id','is_main','is_closed','is_24_7','rating',
             'sort','dir','booking_id'] as $fk) {
    if (isset($_GET[$fk]) && $_GET[$fk] !== '') {
        $filters[$fk] = trim($_GET[$fk]);
    }
}

$table = AdminTable::tryFromValue($tableRaw);

// Гейт доступу за роллю (content_manager бачить лише контент-таблиці)
if ($table !== null) {
    requireTableAccess($table);
}

// ── Репозиторій ───────────────────────────────────────────────────────────────
function getRepo(AdminTable $t): BaseRepository
{
    $map = [
        AdminTable::Users->value             => ['UserRepository',             fn() => new UserRepository()],
        AdminTable::Coworkings->value        => ['CoworkingRepository',        fn() => new CoworkingRepository()],
        AdminTable::Workspaces->value        => ['WorkspaceRepository',        fn() => new WorkspaceRepository()],
        AdminTable::OperatingHours->value    => ['OperatingHoursRepository',   fn() => new OperatingHoursRepository()],
        AdminTable::Features->value          => ['FeatureRepository',          fn() => new FeatureRepository()],
        AdminTable::CoworkingFeatures->value => ['CoworkingFeatureRepository', fn() => new CoworkingFeatureRepository()],
        AdminTable::Gallery->value           => ['GalleryRepository',          fn() => new GalleryRepository()],
        AdminTable::Bookings->value          => ['BookingRepository',          fn() => new BookingRepository()],
        AdminTable::BookingSlots->value      => ['BookingSlotRepository',      fn() => new BookingSlotRepository()],
        AdminTable::Reviews->value           => ['ReviewRepository',           fn() => new ReviewRepository()],
    ];

    require_once __DIR__ . '/db/BaseRepository.php';
    require_once __DIR__ . '/db/' . $map[$t->value][0] . '.php';
    return ($map[$t->value][1])();
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $table !== null) {

    if ($table->isReadOnly() && $action !== 'delete') {
        flashSet(FlashType::Error, 'Ця таблиця доступна тільки для читання.');
        redirect("/admin/?t={$table->value}");
    }

    require_once __DIR__ . '/db/BaseRepository.php';
    require_once __DIR__ . '/db/AuditLogRepository.php';
    $audit = new AuditLogRepository();
    $aUid  = (int) ($_SESSION['admin_id'] ?? 0);
    $aUnm  = (string) ($_SESSION['admin_name'] ?? 'admin');

    // ── DELETE ────────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        // CoworkingFeatures має складений ключ
        if ($table === AdminTable::CoworkingFeatures) {
            $cwId = (int) ($_GET['coworking_id'] ?? 0);
            $ftId = (int) ($_GET['feature_id']   ?? 0);
            try {
                require_once __DIR__ . '/db/CoworkingFeatureRepository.php';
                (new CoworkingFeatureRepository())->delete($cwId, $ftId);
                $audit->log($aUid, $aUnm, 'DELETE', $table->value, null,
                    "coworking_id={$cwId}, feature_id={$ftId}");
                flashSet(FlashType::Ok, "Зв'язок коворкінг-зручність видалено.");
            } catch (PDOException) {
                flashSet(FlashType::Error, 'Неможливо видалити.');
            }
            redirect("/admin/?t={$table->value}");
        }

        if ($id > 0) {
            try {
                // При видаленні слота — запамʼятати booking_id, щоб перерахувати total_price після
                $slotBookingId = null;
                if ($table === AdminTable::BookingSlots) {
                    require_once __DIR__ . '/db/BookingSlotRepository.php';
                    $existingSlot = (new BookingSlotRepository())->findById($id);
                    $slotBookingId = $existingSlot ? (int) $existingSlot['booking_id'] : null;
                }

                // Журнал ведемо тільки для змін над адмінами/контент-менеджерами.
                // Якщо видаляємо звичайного користувача сайту — не засмічуємо лог.
                $skipAudit = false;
                if ($table === AdminTable::Users) {
                    require_once __DIR__ . '/db/UserRepository.php';
                    $victim = (new UserRepository())->findById($id);
                    $victimRole = $victim ? UserRole::tryFrom((string) $victim['role']) : null;
                    $skipAudit = !$victimRole || !$victimRole->canAccessAdmin();
                }

                getRepo($table)->delete($id);

                if ($slotBookingId !== null) {
                    require_once __DIR__ . '/db/BookingRepository.php';
                    (new BookingRepository())->recalcTotalPrice($slotBookingId);
                }

                if (!$skipAudit) {
                    $audit->log($aUid, $aUnm, 'DELETE', $table->value, $id,
                        "Видалено запис #{$id}");
                }
                flashSet(FlashType::Ok, 'Запис #' . $id . ' видалено.');
            } catch (PDOException) {
                flashSet(FlashType::Error, 'Неможливо видалити: запис пов\'язаний з іншими даними.');
            }
            redirect("/admin/?t={$table->value}");
        }
    }

    // ── ADD / EDIT ────────────────────────────────────────────────────────────
    if (in_array($action, ['add', 'edit'])) {
        $warnReason  = null;
        $warnDetails = [];
        // За замовчуванням пишемо у журнал. Нижче можемо вимкнути для
        // CRUD над звичайними site-юзерами (role=user).
        $skipAudit   = false;

        // ── Users ─────────────────────────────────────────────────────────────
        if ($table === AdminTable::Users) {
            require_once __DIR__ . '/db/UserRepository.php';
            $repo     = new UserRepository();
            $fullName = trim($_POST['full_name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $roleRaw  = trim($_POST['role'] ?? 'user');
            $role     = UserRole::tryFrom($roleRaw) ?? UserRole::User;
            $pass     = $_POST['password'] ?? '';

            // При edit — розглядаємо і стару, і нову роль: якщо обидві —
            // звичайні користувачі, зміна не потрапляє до журналу.
            $prevRole = null;
            if ($action === 'edit') {
                $prev     = $repo->findById($id);
                $prevRole = $prev ? UserRole::tryFrom((string) $prev['role']) : null;
            }
            $touchesStaff = $role->canAccessAdmin()
                || ($prevRole !== null && $prevRole->canAccessAdmin());
            $skipAudit    = !$touchesStaff;

            if ($action === 'add') {
                if (strlen($pass) < 8) {
                    $warnReason  = WarnReason::WeakPassword;
                    $warnDetails = ['Пароль повинен містити мінімум 8 символів.'];
                } else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $repo->create($fullName, $email, $hash, $phone, $role->value);
                    flashSet(FlashType::Ok, "Користувача «{$fullName}» створено.");
                }
            } else {
                $repo->update($id, $fullName, $email, $phone, $role->value);
                if ($pass !== '') {
                    if (strlen($pass) < 8) {
                        $warnReason  = WarnReason::WeakPassword;
                        $warnDetails = ['Пароль повинен містити мінімум 8 символів.'];
                    } else {
                        $hash = password_hash($pass, PASSWORD_BCRYPT);
                        $repo->updatePassword($id, $hash);
                    }
                }
                if ($warnReason === null) {
                    flashSet(FlashType::Ok, "Користувача «{$fullName}» оновлено.");
                }
            }
        }

        // ── Coworkings ────────────────────────────────────────────────────────
        elseif ($table === AdminTable::Coworkings) {
            require_once __DIR__ . '/db/CoworkingRepository.php';
            $repo   = new CoworkingRepository();
            $name   = trim($_POST['name'] ?? '');
            $addr   = trim($_POST['address'] ?? '');
            $city   = trim($_POST['city'] ?? '');
            $desc   = trim($_POST['description'] ?? '');
            $is247  = (int) ($_POST['is_24_7'] ?? 0);
            $latRaw = trim((string) ($_POST['latitude']  ?? ''));
            $lngRaw = trim((string) ($_POST['longitude'] ?? ''));
            $lat    = $latRaw === '' ? null : (float) str_replace(',', '.', $latRaw);
            $lng    = $lngRaw === '' ? null : (float) str_replace(',', '.', $lngRaw);

            if ($action === 'add') {
                $repo->create($name, $addr, $city, $desc, $is247, $lat, $lng);
                flashSet(FlashType::Ok, "Коворкінг «{$name}» створено.");
            } else {
                $repo->update($id, $name, $addr, $city, $desc, $is247, $lat, $lng);
                flashSet(FlashType::Ok, "Коворкінг «{$name}» оновлено.");
            }
        }

        // ── Workspaces ────────────────────────────────────────────────────────
        elseif ($table === AdminTable::Workspaces) {
            require_once __DIR__ . '/db/CoworkingRepository.php';
            require_once __DIR__ . '/db/WorkspaceRepository.php';
            $cwRepo  = new CoworkingRepository();
            $wsRepo  = new WorkspaceRepository();
            $cwId    = (int) ($_POST['coworking_id'] ?? 0);
            $name    = trim($_POST['name'] ?? '');
            $typeRaw = trim($_POST['type_key'] ?? '');
            $price   = (float) ($_POST['price_per_hour'] ?? 0);
            $cap     = (int) ($_POST['capacity'] ?? 0);
            $type    = WorkspaceType::tryFrom($typeRaw) ?? WorkspaceType::Open;

            if (!$cwRepo->existsById('coworkings', $cwId)) {
                $warnReason  = WarnReason::CoworkingNotFound;
                $warnDetails = ["Вказаний coworking_id: {$cwId}"];
            } else {
                if ($action === 'add') {
                    $wsRepo->create($cwId, $name, $type, $price, $cap);
                    flashSet(FlashType::Ok, "Робоче місце «{$name}» створено.");
                } else {
                    $wsRepo->update($id, $cwId, $name, $type, $price, $cap);
                    flashSet(FlashType::Ok, "Робоче місце «{$name}» оновлено.");
                }
            }
        }

        // ── OperatingHours ────────────────────────────────────────────────────
        elseif ($table === AdminTable::OperatingHours) {
            require_once __DIR__ . '/db/OperatingHoursRepository.php';
            $repo      = new OperatingHoursRepository();
            $cwId      = (int) ($_POST['coworking_id'] ?? 0);
            $dayOfWeek = (int) ($_POST['day_of_week'] ?? 1);
            $openTime  = trim($_POST['open_time'] ?? '') ?: null;
            $closeTime = trim($_POST['close_time'] ?? '') ?: null;
            $isClosed  = (bool)(int)($_POST['is_closed'] ?? 0);

            if (!$repo->coworkingExists($cwId)) {
                $warnReason  = WarnReason::CoworkingNotFound;
                $warnDetails = ["Вказаний coworking_id: {$cwId}"];
            } else {
                if ($action === 'add') {
                    $repo->create($cwId, $dayOfWeek, $openTime, $closeTime, $isClosed);
                    flashSet(FlashType::Ok, 'Графік роботи додано.');
                } else {
                    $repo->update($id, $cwId, $dayOfWeek, $openTime, $closeTime, $isClosed);
                    flashSet(FlashType::Ok, 'Графік роботи оновлено.');
                }
            }
        }

        // ── Features ──────────────────────────────────────────────────────────
        elseif ($table === AdminTable::Features) {
            require_once __DIR__ . '/db/FeatureRepository.php';
            $repo    = new FeatureRepository();
            $name    = trim($_POST['name'] ?? '');
            $iconKey = trim($_POST['icon_key'] ?? '');

            if ($action === 'add') {
                $repo->create($name, $iconKey);
                flashSet(FlashType::Ok, "Зручність «{$name}» створено.");
            } else {
                $repo->update($id, $name, $iconKey);
                flashSet(FlashType::Ok, "Зручність «{$name}» оновлено.");
            }
        }

        // ── CoworkingFeatures ─────────────────────────────────────────────────
        elseif ($table === AdminTable::CoworkingFeatures) {
            require_once __DIR__ . '/db/CoworkingFeatureRepository.php';
            $repo  = new CoworkingFeatureRepository();
            $cwId  = (int) ($_POST['coworking_id'] ?? 0);
            $ftId  = (int) ($_POST['feature_id']   ?? 0);

            if (!$repo->existsById('coworkings', $cwId)) {
                $warnReason  = WarnReason::CoworkingNotFound;
                $warnDetails = ["coworking_id: {$cwId}"];
            } elseif (!$repo->existsById('features', $ftId)) {
                $warnReason  = WarnReason::RecordNotFound;
                $warnDetails = ["feature_id: {$ftId}", 'Зручність не знайдено.'];
            } else {
                $repo->create($cwId, $ftId);
                flashSet(FlashType::Ok, 'Зручність прив\'язано до коворкінгу.');
            }
        }

        // ── Gallery ───────────────────────────────────────────────────────────
        elseif ($table === AdminTable::Gallery) {
            require_once __DIR__ . '/db/GalleryRepository.php';
            require_once __DIR__ . '/db/CoworkingRepository.php';

            $repo        = new GalleryRepository();
            $coworkingRp = new CoworkingRepository();

            $entityId = (int) ($_POST['entity_id'] ?? 0); // це coworking_id
            $imageUrl = trim($_POST['image_url'] ?? '');
            $isMain   = (bool)(int)($_POST['is_main'] ?? 0);

            // перевірка існування коворкінгу
            if ($entityId <= 0 || !$coworkingRp->exists($entityId)) {
                $warnReason  = WarnReason::GalleryEntityNotFound;
                $warnDetails = [
                    "Коворкінг ID: {$entityId}"
                ];
            } else {
                if ($action === 'add') {
                    $repo->create($entityId, $imageUrl, $isMain);
                    flashSet(FlashType::Ok, 'Фото додано до галереї.');
                } else {
                    $repo->update($id, $entityId, $imageUrl, $isMain);
                    flashSet(FlashType::Ok, 'Фото оновлено.');
                }
            }
        }

        // ── Bookings ──────────────────────────────────────────────────────────
        elseif ($table === AdminTable::Bookings) {
            require_once __DIR__ . '/db/WorkspaceRepository.php';
            require_once __DIR__ . '/db/BookingRepository.php';
            $wsRepo    = new WorkspaceRepository();
            $bRepo     = new BookingRepository();
            $userId    = (int) ($_POST['user_id']      ?? 0);
            $wsId      = (int) ($_POST['workspace_id'] ?? 0);
            $statusRaw = trim($_POST['status'] ?? '');
            $status    = BookingStatus::tryFrom($statusRaw) ?? BookingStatus::Pending;

            if (!$wsRepo->existsById('workspaces', $wsId)) {
                $warnReason  = WarnReason::WorkspaceNotFound;
                $warnDetails = ["workspace_id: {$wsId}"];
            } else {
                // total_price — derived з booking_slots: новий booking починається з 0,
                // далі перераховується на кожну зміну слотів.
                if ($action === 'add') {
                    $newId = $bRepo->create($userId, $wsId, $status, 0.0);
                    $bRepo->recalcTotalPrice($newId);
                    flashSet(FlashType::Ok, 'Бронювання створено.');
                } else {
                    $existingPrice = $bRepo->getTotalPrice($id);
                    $bRepo->update($id, $userId, $wsId, $status, $existingPrice);
                    // Воркспейс могли змінити → перерахувати під нову ціну
                    $bRepo->recalcTotalPrice($id);
                    flashSet(FlashType::Ok, 'Бронювання оновлено.');
                }
            }
        }

        // ── BookingSlots ──────────────────────────────────────────────────────
        elseif ($table === AdminTable::BookingSlots) {
            require_once __DIR__ . '/db/BookingSlotRepository.php';
            require_once __DIR__ . '/db/BookingRepository.php';
            $slotRepo  = new BookingSlotRepository();
            $bRepo     = new BookingRepository();
            $bookingId = (int) ($_POST['booking_id'] ?? 0);
            $startTime = trim($_POST['start_time'] ?? '');
            $endTime   = trim($_POST['end_time']   ?? '');

            if (!$slotRepo->bookingExists($bookingId)) {
                $warnReason  = WarnReason::BookingNotFound;
                $warnDetails = ["booking_id: {$bookingId}", 'Спочатку створіть бронювання.'];
            } elseif ($startTime >= $endTime) {
                $warnReason  = WarnReason::InvalidTimeRange;
                $warnDetails = ["Початок: {$startTime}", "Кінець: {$endTime}"];
            } else {
                // Може змінитись booking_id при редагуванні — треба знати старий, щоб перерахувати обидва
                $oldBookingId = null;
                if ($action === 'edit') {
                    $existing = $slotRepo->findById($id);
                    $oldBookingId = $existing ? (int) $existing['booking_id'] : null;
                }

                if ($action === 'add') {
                    $slotRepo->create($bookingId, $startTime, $endTime);
                    $bRepo->recalcTotalPrice($bookingId);
                    flashSet(FlashType::Ok, "Слот {$startTime} – {$endTime} додано.");
                } else {
                    $slotRepo->update($id, $bookingId, $startTime, $endTime);
                    $bRepo->recalcTotalPrice($bookingId);
                    if ($oldBookingId !== null && $oldBookingId !== $bookingId) {
                        $bRepo->recalcTotalPrice($oldBookingId);
                    }
                    flashSet(FlashType::Ok, 'Слот оновлено.');
                }
            }
        }

        // ── Warn ──────────────────────────────────────────────────────────────
        if ($warnReason !== null) {
            $pageTitle   = $warnReason->title();
            $activeTable = $table;
            include __DIR__ . '/ui/partials/layout_head.php';
            $warnBackUrl   = "/admin/?t={$table->value}&a={$action}" . ($id ? "&id={$id}" : '');
            $warnBackLabel = 'Повернутись до форми';
            $warnAction    = null;
            include __DIR__ . '/ui/views/view_warning.php';
            include __DIR__ . '/ui/partials/layout_foot.php';
            exit;
        }

        // Логуємо успішну дію add/edit (крім керування звичайними site-юзерами).
        if (!$skipAudit) {
            $audit->log(
                $aUid, $aUnm,
                $action === 'add' ? 'INSERT' : 'UPDATE',
                $table->value,
                $id ?: null,
                ($action === 'add' ? 'Створено запис у ' : 'Оновлено запис #' . $id . ' у ') . $table->value,
            );
        }

        redirect("/admin/?t={$table->value}");
    }
}

// ── GET ───────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/ui/components/cell_renderer.php';
require_once __DIR__ . '/ui/components/form_field.php';

// Дашборд
if ($table === null) {
    $pageTitle   = 'Дашборд';
    $activeTable = null;
    $stats = [];
    foreach ($currentRole->allowedTables() as $t) {
        require_once __DIR__ . '/db/BaseRepository.php';
        try {
            $stats[$t->value] = getRepo($t)->total();
        } catch (Throwable) {
            $stats[$t->value] = '—';
        }
    }
    include __DIR__ . '/ui/partials/layout_head.php';
    include __DIR__ . '/ui/views/view_dashboard.php';
    include __DIR__ . '/ui/partials/layout_foot.php';
    exit;
}

$activeTable = $table;
$pageTitle   = $table->label();

// ADD / EDIT
if (in_array($action, ['add', 'edit'])) {
    if ($table->isReadOnly()) redirect("/admin/?t={$table->value}");

    $editRow = null;
    if ($action === 'edit' && $id > 0) {
        require_once __DIR__ . '/db/BaseRepository.php';
        $editRow = getRepo($table)->findById($id);
        if ($editRow === null) {
            include __DIR__ . '/ui/partials/layout_head.php';
            $warnReason = WarnReason::RecordNotFound; $warnBackUrl = "/admin/?t={$table->value}";
            $warnBackLabel = 'Назад до списку'; $warnDetails = ["ID: {$id}"]; $warnAction = null;
            include __DIR__ . '/ui/views/view_warning.php';
            include __DIR__ . '/ui/partials/layout_foot.php';
            exit;
        }
    }

    include __DIR__ . '/ui/partials/layout_head.php';
    include __DIR__ . '/ui/views/view_form.php';
    include __DIR__ . '/ui/partials/layout_foot.php';
    exit;
}

// LIST
require_once __DIR__ . '/db/BaseRepository.php';
$repo   = getRepo($table);
$offset = ($page - 1) * $perPage;
$rows   = $repo->findAll($offset, $perPage, $filters);
$total  = $repo->total($filters);

include __DIR__ . '/ui/partials/layout_head.php';
include __DIR__ . '/ui/views/view_list.php';
include __DIR__ . '/ui/partials/layout_foot.php';
