<?php
// site/controllers/AuthController.php

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->render('auth/login', [
            'title'  => 'Вхід',
            'return' => Request::str('return'),
            'email'  => '',
            'errors' => [],
        ]);
    }

    public function login(): void
    {
        csrfCheck();
        $email    = mb_strtolower(trim(Request::post('email', '')));
        $password = (string) Request::post('password', '');
        $return   = Request::post('return', '') ?: siteUrl('home');

        $errors = [];
        if ($email === '')     $errors['email']    = 'Введіть email';
        if ($password === '')  $errors['password'] = 'Введіть пароль';

        if (!$errors) {
            $user = (new UserModel())->findByEmail($email);
            if (!$user || !password_verify($password, (string) $user['password_hash'])) {
                $errors['general'] = 'Невірний email або пароль';
            } else {
                Auth::login((int) $user['id'], (string) ($user['role'] ?? 'user'), (string) $user['full_name']);
                flash('ok', 'Ви увійшли');
                Response::redirect($return);
                return;
            }
        }

        $this->render('auth/login', [
            'title'  => 'Вхід',
            'return' => $return,
            'email'  => $email,
            'errors' => $errors,
        ]);
    }

    public function registerForm(): void
    {
        $this->render('auth/register', [
            'title'  => 'Реєстрація',
            'form'   => ['full_name' => '', 'email' => '', 'phone' => ''],
            'errors' => [],
        ]);
    }

    public function register(): void
    {
        csrfCheck();
        $fullName = trim((string) Request::post('full_name', ''));
        $email    = mb_strtolower(trim((string) Request::post('email', '')));
        $phone    = trim((string) Request::post('phone', ''));
        $password = (string) Request::post('password', '');
        $password2 = (string) Request::post('password_confirm', '');

        $errors = [];
        if (mb_strlen($fullName) < 2) $errors['full_name'] = 'Введіть ПІБ';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Невірний email';
        if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/\s|-/', '', $phone))) {
            $errors['phone'] = 'Невірний номер телефону (формат +380XXXXXXXXX)';
        }
        if (mb_strlen($password) < 6) $errors['password'] = 'Пароль мінімум 6 символів';
        if ($password !== $password2)  $errors['password_confirm'] = 'Паролі не співпадають';

        $um = new UserModel();
        if (!$errors && $um->emailExists($email)) {
            $errors['email'] = 'Користувач з таким email вже існує';
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $id = $um->register($fullName, $email, $phone, $hash);
            Auth::login($id, 'user', $fullName);
            flash('ok', 'Реєстрація успішна. Вітаємо в coWork!');
            Response::redirect(siteUrl('home'));
            return;
        }

        $this->render('auth/register', [
            'title'  => 'Реєстрація',
            'form'   => ['full_name' => $fullName, 'email' => $email, 'phone' => $phone],
            'errors' => $errors,
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        session_start();
        flash('ok', 'Ви вийшли з акаунту');
        Response::redirect(siteUrl('home'));
    }
}
