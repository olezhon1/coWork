<?php
// admin/profile.php — власний профіль поточного адміна/контент-менеджера.

require_once __DIR__ . '/config/bootstrap.php';
requireAdmin();

require_once __DIR__ . '/db/UserRepository.php';
require_once __DIR__ . '/db/AuditLogRepository.php';

$repo  = new UserRepository();
$audit = new AuditLogRepository();

$uid  = (int) ($_SESSION['admin_id'] ?? 0);
$me   = $uid ? $repo->findById($uid) : null;
$role = currentAdminRole() ?? UserRole::User;

if (!$me) {
    flashSet(FlashType::Error, 'Обліковий запис не знайдено.');
    redirect('/admin/logout.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $phone    = trim((string) ($_POST['phone'] ?? ''));
    $pass     = (string) ($_POST['password'] ?? '');
    $pass2    = (string) ($_POST['password_confirm'] ?? '');

    if ($fullName === '') $errors[] = "Ім'я не може бути порожнім.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некоректний email.';

    // перевірка унікальності email (якщо змінено)
    if ($email !== '' && strcasecmp($email, (string) $me['email']) !== 0) {
        $other = $repo->findByEmail($email);
        if ($other && (int) $other['id'] !== $uid) {
            $errors[] = 'Цей email уже зайнятий іншим користувачем.';
        }
    }

    if ($pass !== '' || $pass2 !== '') {
        if (strlen($pass) < 8) {
            $errors[] = 'Пароль повинен містити мінімум 8 символів.';
        } elseif ($pass !== $pass2) {
            $errors[] = 'Паролі не співпадають.';
        }
    }

    if (!$errors) {
        // Роль не змінюється через профіль — беремо з БД, щоб юзер не міг підвищити собі права.
        $repo->update($uid, $fullName, $email, $phone, (string) $me['role']);

        $changes = [];
        if ($fullName !== $me['full_name']) $changes[] = "імʼя: «{$me['full_name']}» → «{$fullName}»";
        if ($email    !== $me['email'])     $changes[] = "email: «{$me['email']}» → «{$email}»";
        if ($phone    !== ($me['phone'] ?? '')) $changes[] = "телефон оновлено";

        if ($pass !== '') {
            $repo->updatePassword($uid, password_hash($pass, PASSWORD_BCRYPT));
            $changes[] = 'пароль змінено';
        }

        $audit->log(
            $uid, $fullName,
            'PROFILE',
            'users',
            $uid,
            $changes ? implode('; ', $changes) : 'Без змін',
        );

        $_SESSION['admin_name'] = $fullName;
        flashSet(FlashType::Ok, 'Профіль оновлено.');
        redirect('/admin/profile.php');
    }

    // при помилці — підставляємо те, що ввів користувач, щоб не зникало
    $me['full_name'] = $fullName;
    $me['email']     = $email;
    $me['phone']     = $phone;
}

$pageTitle   = 'Мій профіль';
$activeAdmin = 'profile';

include __DIR__ . '/ui/partials/layout_head.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Мій профіль</div>
    <div class="page-sub">
      Роль: <strong><?= h($role->label()) ?></strong>.
      <?= $role->isSuperAdmin()
          ? 'Повний доступ: усі таблиці, Сервіс, Налаштування, Журнал дій.'
          : 'Доступ лише до контент-таблиць (коворкінги, місця, графік, зручності, галерея).' ?>
    </div>
  </div>
</div>

<?php if ($errors): ?>
  <div class="alert alert-err" style="margin-bottom:1rem">
    <?= icon('warning') ?>
    <span><?= h(implode(' ', $errors)) ?></span>
  </div>
<?php endif ?>

<form method="post" class="card" style="max-width:640px">
  <div style="margin-bottom:1rem">
    <label class="form-label" for="pf_name">Повне імʼя</label>
    <input type="text" id="pf_name" name="full_name"
           class="input"
           value="<?= h((string) $me['full_name']) ?>" required>
  </div>

  <div style="margin-bottom:1rem">
    <label class="form-label" for="pf_email">Email</label>
    <input type="email" id="pf_email" name="email"
           class="input"
           value="<?= h((string) $me['email']) ?>" required>
  </div>

  <div style="margin-bottom:1rem">
    <label class="form-label" for="pf_phone">Телефон</label>
    <input type="text" id="pf_phone" name="phone"
           class="input"
           value="<?= h((string) ($me['phone'] ?? '')) ?>">
  </div>

  <fieldset style="margin-top:1.25rem;padding:1rem;border:1px solid var(--border);border-radius:var(--radius)">
    <legend style="font-size:var(--fs-sm);color:var(--text2);padding:0 .4rem">Зміна пароля (необовʼязково)</legend>
    <div style="margin-bottom:.75rem">
      <label class="form-label" for="pf_pw">Новий пароль</label>
      <input type="password" id="pf_pw" name="password"
             class="input" autocomplete="new-password" minlength="8"
             placeholder="Залиш порожнім, щоб не змінювати">
    </div>
    <div>
      <label class="form-label" for="pf_pw2">Повторіть новий пароль</label>
      <input type="password" id="pf_pw2" name="password_confirm"
             class="input" autocomplete="new-password" minlength="8">
    </div>
  </fieldset>

  <div style="display:flex;gap:.5rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
    <button type="submit" class="btn btn-accent">
      <?= icon('save') ?> Зберегти
    </button>
    <a href="/admin/" class="btn">Скасувати</a>
  </div>
</form>

<style>
  .form-label { display:block;font-size:var(--fs-sm);color:var(--text);margin-bottom:.3rem;font-weight:500 }
  .input { width:100%;padding:.5rem .7rem;font-size:var(--fs-sm);border:1px solid var(--border2);border-radius:var(--radius);background:var(--surface);color:var(--text);font-family:inherit }
</style>

<?php include __DIR__ . '/ui/partials/layout_foot.php'; ?>
