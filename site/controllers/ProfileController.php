<?php
// site/controllers/ProfileController.php

class ProfileController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $bookings = (new BookingModel())->findByUser(Auth::id());

        $this->render('profile/index', [
            'title'        => 'Мій профіль',
            'user'         => Auth::user(),
            'bookings'     => $bookings,
            'infoErrors'   => [],
            'pwErrors'     => [],
            'form'         => null,
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        csrfCheck();

        $um = new UserModel();
        $me = Auth::user();
        $uid = Auth::id();

        $fullName = trim((string) Request::post('full_name', ''));
        $email    = mb_strtolower(trim((string) Request::post('email', '')));
        $phone    = trim((string) Request::post('phone', ''));

        $errors = [];
        if (mb_strlen($fullName) < 2) $errors['full_name'] = 'Введіть ПІБ';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Невірний email';
        if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/\s|-/', '', $phone))) {
            $errors['phone'] = 'Невірний номер телефону (формат +380XXXXXXXXX)';
        }

        if (!$errors && strcasecmp($email, (string) $me['email']) !== 0) {
            $other = $um->findByEmail($email);
            if ($other && (int) $other['id'] !== $uid) {
                $errors['email'] = 'Користувач з таким email вже існує';
            }
        }

        if (!$errors) {
            $um->updateProfile($uid, $fullName, $email, $phone);
            $_SESSION['user_name'] = $fullName;
            flash('ok', 'Профіль оновлено');
            Response::redirect(siteUrl('profile'));
            return;
        }

        // Перерендеримо сторінку з помилками, зберігши введені значення
        $bookings = (new BookingModel())->findByUser($uid);
        $userOverride = [
            'id'        => $uid,
            'full_name' => $fullName,
            'email'     => $email,
            'phone'     => $phone,
        ] + ($me ?? []);

        $this->render('profile/index', [
            'title'      => 'Мій профіль',
            'user'       => $userOverride,
            'bookings'   => $bookings,
            'infoErrors' => $errors,
            'pwErrors'   => [],
            'form'       => 'info',
        ]);
    }

    public function updatePassword(): void
    {
        $this->requireLogin();
        csrfCheck();

        $um = new UserModel();
        $me = $um->findById(Auth::id());
        if (!$me) {
            flash('err', 'Сесія застаріла, увійдіть ще раз');
            Response::redirect(siteUrl('login'));
            return;
        }

        $old  = (string) Request::post('old_password', '');
        $new1 = (string) Request::post('new_password', '');
        $new2 = (string) Request::post('new_password_confirm', '');

        $errors = [];
        if ($old === '' || $new1 === '' || $new2 === '') {
            $errors['general'] = 'Заповніть усі три поля';
        } elseif (!password_verify($old, (string) $me['password_hash'])) {
            $errors['old_password'] = 'Старий пароль невірний';
        } elseif (mb_strlen($new1) < 6) {
            $errors['new_password'] = 'Пароль мінімум 6 символів';
        } elseif ($new1 !== $new2) {
            $errors['new_password_confirm'] = 'Паролі не співпадають';
        } elseif ($new1 === $old) {
            $errors['new_password'] = 'Новий пароль має відрізнятись від старого';
        }

        if (!$errors) {
            $um->updatePassword(Auth::id(), password_hash($new1, PASSWORD_BCRYPT));
            flash('ok', 'Пароль змінено');
            Response::redirect(siteUrl('profile'));
            return;
        }

        $bookings = (new BookingModel())->findByUser(Auth::id());
        $this->render('profile/index', [
            'title'      => 'Мій профіль',
            'user'       => Auth::user(),
            'bookings'   => $bookings,
            'infoErrors' => [],
            'pwErrors'   => $errors,
            'form'       => 'password',
        ]);
    }

    public function cancelBooking(): void
    {
        $this->requireLogin();
        csrfCheck();
        $id = Request::int('booking_id');
        if ((new BookingModel())->cancel($id, Auth::id())) {
            flash('ok', 'Бронювання скасовано');
        } else {
            flash('err', 'Не вдалось скасувати бронювання');
        }
        Response::redirect(siteUrl('profile'));
    }
}
