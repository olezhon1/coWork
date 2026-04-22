<?php
// login.php
require_once __DIR__ . '/config/bootstrap.php';

if (isLoggedIn()) redirect('/admin/');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email !== '' && $pass !== '') {
        require_once __DIR__ . '/db/UserRepository.php';
        $repo = new UserRepository();
        $user = $repo->findAdminByEmail($email);

        if ($user && password_verify($pass, $user['password_hash'])) {
            $role = UserRole::tryFrom((string) $user['role']) ?? UserRole::User;
            if ($role->canAccessAdmin()) {
                $_SESSION['admin']      = true;
                $_SESSION['admin_id']   = $user['id'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_role'] = $role->value;
                require_once __DIR__ . '/db/AuditLogRepository.php';
                (new AuditLogRepository())->log(
                    (int) $user['id'], (string) $user['full_name'],
                    'LOGIN', null, null, 'Вхід у адмінку (' . $role->label() . ')',
                );
                redirect('/admin/');
            }
        }
        require_once __DIR__ . '/db/AuditLogRepository.php';
        (new AuditLogRepository())->log(
            null, null, 'LOGIN_FAIL', null, null,
            'Невдала спроба входу: ' . mb_substr($email, 0, 120),
        );
    }
    $error = 'Невірний email або пароль, або недостатньо прав.';
}

$cssVars = require __DIR__ . '/assets/css/variables.php';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Вхід — CoWork Admin</title>
  <style>
    <?= $cssVars ?>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:var(--font-sans);background:var(--bg);color:var(--text);display:flex;align-items:center;justify-content:center;min-height:100vh;font-size:var(--fs-base)}
    .login-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:2.25rem 2.5rem;width:100%;max-width:380px;box-shadow:var(--shadow-md)}
    .login-logo{font-size:var(--fs-xl);font-weight:500;color:var(--accent-dk);text-align:center;margin-bottom:.3rem}
    .login-logo em{font-style:normal;color:var(--warm)}
    .login-sub{font-size:var(--fs-sm);color:var(--text3);text-align:center;margin-bottom:1.75rem}
    .form-group{margin-bottom:.875rem}
    .form-label{font-size:var(--fs-xs);color:var(--text2);font-weight:500;display:block;margin-bottom:.35rem}
    input[type=email],input[type=password]{width:100%;padding:.55rem .9rem;font-size:var(--fs-sm);border:1px solid var(--border2);border-radius:var(--radius);background:var(--surface);color:var(--text);font-family:inherit}
    input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(47,107,85,.15)}
    .btn-submit{width:100%;padding:.6rem 1rem;font-size:var(--fs-base);font-weight:500;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);cursor:pointer;font-family:inherit;transition:.12s;margin-top:.5rem}
    .btn-submit:hover{background:var(--accent-dk)}
    .err{background:var(--red-lt);color:var(--red);padding:.625rem .9rem;border-radius:var(--radius);font-size:var(--fs-sm);margin-bottom:.875rem;border:1px solid #E0BCBC}
  </style>
</head>
<body>
  <div class="login-wrap">
    <div class="login-logo">Co<em>Work</em></div>
    <div class="login-sub">Панель адміністратора</div>

    <?php if ($error): ?>
      <div class="err"><?= h($error) ?></div>
    <?php endif ?>

    <form method="post">
      <div class="form-group">
        <label class="form-label" for="u">Email</label>
        <input type="email" id="u" name="email"
               value="<?= h($_POST['email'] ?? '') ?>"
               autofocus autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label" for="p">Пароль</label>
        <input type="password" id="p" name="password" autocomplete="current-password">
      </div>
      <button type="submit" class="btn-submit">Увійти</button>
    </form>
  </div>
</body>
</html>
