<?php
// admin/profile.php — власний профіль поточного адміна/контент-менеджера.
//
// Дві окремі форми:
//   op=info     — редагування імʼя/email/телефону
//   op=password — зміна пароля (вимагає коректний старий пароль)
//
// Дії профілю у журнал не пишуться — це персональні налаштування користувача.

require_once __DIR__ . '/config/bootstrap.php';
requireAdmin();

require_once __DIR__ . '/db/UserRepository.php';

$repo = new UserRepository();

$uid  = (int) ($_SESSION['admin_id'] ?? 0);
$me   = $uid ? $repo->findById($uid) : null;
$role = currentAdminRole() ?? UserRole::User;

if (!$me) {
    flashSet(FlashType::Error, 'Обліковий запис не знайдено.');
    redirect('/admin/logout.php');
}

$infoErrors = [];
$pwErrors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = (string) ($_POST['op'] ?? '');

    // ── Форма «Особисті дані» ─────────────────────────────────────────────
    if ($op === 'info') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $phone    = trim((string) ($_POST['phone'] ?? ''));

        if ($fullName === '') $infoErrors[] = "Ім'я не може бути порожнім.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $infoErrors[] = 'Некоректний email.';

        if ($email !== '' && strcasecmp($email, (string) $me['email']) !== 0) {
            $other = $repo->findByEmail($email);
            if ($other && (int) $other['id'] !== $uid) {
                $infoErrors[] = 'Цей email уже зайнятий іншим користувачем.';
            }
        }

        if (!$infoErrors) {
            // Роль беремо з БД — заборонено міняти через профіль.
            $repo->update($uid, $fullName, $email, $phone, (string) $me['role']);
            $_SESSION['admin_name'] = $fullName;
            flashSet(FlashType::Ok, 'Особисті дані оновлено.');
            redirect('/admin/profile.php');
        }

        $me['full_name'] = $fullName;
        $me['email']     = $email;
        $me['phone']     = $phone;
    }

    // ── Форма «Зміна пароля» ──────────────────────────────────────────────
    elseif ($op === 'password') {
        $old   = (string) ($_POST['old_password'] ?? '');
        $new1  = (string) ($_POST['new_password'] ?? '');
        $new2  = (string) ($_POST['new_password_confirm'] ?? '');

        if ($old === '' || $new1 === '' || $new2 === '') {
            $pwErrors[] = 'Заповніть усі три поля.';
        } elseif (!password_verify($old, (string) $me['password_hash'])) {
            $pwErrors[] = 'Старий пароль невірний.';
        } elseif (strlen($new1) < 8) {
            $pwErrors[] = 'Новий пароль повинен містити мінімум 8 символів.';
        } elseif ($new1 !== $new2) {
            $pwErrors[] = 'Новий пароль і підтвердження не співпадають.';
        } elseif ($new1 === $old) {
            $pwErrors[] = 'Новий пароль не повинен співпадати зі старим.';
        }

        if (!$pwErrors) {
            $repo->updatePassword($uid, password_hash($new1, PASSWORD_BCRYPT));
            flashSet(FlashType::Ok, 'Пароль змінено.');
            redirect('/admin/profile.php');
        }
    }
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

<div class="profile-grid">
  <!-- ── Особисті дані ─────────────────────────────────────────────────── -->
  <form method="post" class="card" style="max-width:640px">
    <div class="card-title"><?= icon('user') ?> Особисті дані</div>
    <div class="card-sub">Імʼя, email і телефон, видимі іншим адміністраторам.</div>
    <input type="hidden" name="op" value="info">

    <?php if ($infoErrors): ?>
      <div class="alert alert-err" style="margin-bottom:1rem">
        <?= icon('warning') ?><span><?= h(implode(' ', $infoErrors)) ?></span>
      </div>
    <?php endif ?>

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

    <div style="display:flex;gap:.5rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
      <button type="submit" class="btn btn-accent"><?= icon('save') ?> Зберегти</button>
      <a href="/admin/" class="btn">До дашборда</a>
    </div>
  </form>

  <!-- ── Зміна пароля ──────────────────────────────────────────────────── -->
  <form method="post" class="card" style="max-width:640px" autocomplete="off">
    <div class="card-title"><?= icon('settings') ?> Зміна пароля</div>
    <div class="card-sub">Щоб змінити пароль, введіть поточний і новий двічі.</div>
    <input type="hidden" name="op" value="password">

    <?php if ($pwErrors): ?>
      <div class="alert alert-err" style="margin-bottom:1rem">
        <?= icon('warning') ?><span><?= h(implode(' ', $pwErrors)) ?></span>
      </div>
    <?php endif ?>

    <div style="margin-bottom:1rem">
      <label class="form-label" for="pf_pw_old">Поточний пароль</label>
      <input type="password" id="pf_pw_old" name="old_password"
             class="input" autocomplete="current-password" required>
    </div>

    <div style="margin-bottom:1rem">
      <label class="form-label" for="pf_pw_new">Новий пароль</label>
      <input type="password" id="pf_pw_new" name="new_password"
             class="input" autocomplete="new-password" minlength="8" required>
      <div style="font-size:var(--fs-xs);color:var(--text3);margin-top:.25rem">
        Мінімум 8 символів, має відрізнятись від поточного.
      </div>
    </div>

    <div style="margin-bottom:1rem">
      <label class="form-label" for="pf_pw_new2">Повторіть новий пароль</label>
      <input type="password" id="pf_pw_new2" name="new_password_confirm"
             class="input" autocomplete="new-password" minlength="8" required>
    </div>

    <div style="display:flex;gap:.5rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
      <button type="submit" class="btn btn-accent"><?= icon('save') ?> Змінити пароль</button>
    </div>
  </form>
</div>

<style>
  .profile-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1.25rem;align-items:start }
  .card-title   { font-family:var(--font-serif);font-size:var(--fs-lg);font-weight:600;display:flex;align-items:center;gap:.5rem;margin-bottom:.35rem }
  .card-sub     { font-size:var(--fs-sm);color:var(--text2);margin-bottom:.85rem }
  .form-label   { display:block;font-size:var(--fs-sm);color:var(--text);margin-bottom:.3rem;font-weight:500 }
  .input        { width:100%;padding:.5rem .7rem;font-size:var(--fs-sm);border:1px solid var(--border2);border-radius:var(--radius);background:var(--surface);color:var(--text);font-family:inherit }
</style>

<?php include __DIR__ . '/ui/partials/layout_foot.php'; ?>
