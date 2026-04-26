<?php
// admin/service.php — Сервіс: резервне копіювання, відновлення, архівація таблиць,
// копія БД та перехід на новий обліковий період.

require_once __DIR__ . '/config/bootstrap.php';
requireSuperAdmin();

require_once __DIR__ . '/db/SettingsRepository.php';
require_once __DIR__ . '/db/AuditLogRepository.php';
require_once __DIR__ . '/db/BackupService.php';

$settings = new SettingsRepository();
$audit    = new AuditLogRepository();
$svc      = new BackupService();

$adminId   = (int) ($_SESSION['admin_id'] ?? 0);
$adminName = (string) ($_SESSION['admin_name'] ?? 'admin');

$backupDir = $settings->get('backup_path', '/var/opt/mssql/backup') ?: '/var/opt/mssql/backup';

// ── POST actions ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = (string) ($_POST['op'] ?? '');
    $confirmed = ($_POST['confirm'] ?? '') === 'yes';

    if (!$confirmed) {
        flashSet(FlashType::Error, 'Операцію не підтверджено. Увімкніть прапорець підтвердження.');
        redirect('/admin/service.php');
    }

    try {
        switch ($op) {
            case 'backup': {
                $fileName = trim((string) ($_POST['file_name'] ?? ''));
                if ($fileName === '') {
                    $fileName = 'cowork_' . date('Ymd_His') . '.bak';
                }
                if (!preg_match('/^[A-Za-z0-9._\-]+$/', $fileName)) {
                    throw new \InvalidArgumentException('Неприпустиме ім\'я файлу.');
                }
                $full = rtrim($backupDir, '/\\') . '/' . $fileName;
                $svc->backupDatabase($full);
                $audit->log($adminId, $adminName, 'BACKUP', null, null,
                    "Створено резервну копію: {$full}");
                flashSet(FlashType::Ok, 'Резервну копію створено: ' . $full);
                break;
            }

            case 'restore': {
                $file = (string) ($_POST['backup_file'] ?? '');
                if ($file === '') throw new \InvalidArgumentException('Оберіть файл резервної копії.');
                $full = rtrim($backupDir, '/\\') . '/' . basename($file);
                $svc->restoreDatabase($full);
                $audit->log($adminId, $adminName, 'RESTORE', null, null,
                    "Відновлено БД з {$full}");
                flashSet(FlashType::Ok, 'БД відновлено з ' . $full);
                break;
            }

            case 'archive_table': {
                $table = (string) ($_POST['archive_table'] ?? '');

                $allowed = [
                        'users','coworkings','workspaces','bookings','booking_slots',
                        'reviews','audit_log','settings',
                        'features','coworking_features','gallery','operating_hours'
                ];

                if (!in_array($table, $allowed, true)) {
                    throw new \InvalidArgumentException('Неприпустима таблиця.');
                }

                $archiveDir = $settings->get('archive_path', '/tmp/cowork_archives')
                        ?: '/tmp/cowork_archives';

                if (!is_dir($archiveDir)) {
                    mkdir($archiveDir, 0777, true);
                }

                $file = rtrim($archiveDir, '/\\') . '/' . "{$table}_" . date('Ymd_His') . '.csv';

                $rows = $svc->archiveTableToCsv($table, $file);

                $content = file_get_contents($file);
                if (substr($content, 0, 3) !== "\xEF\xBB\xBF") {
                    file_put_contents($file, "\xEF\xBB\xBF" . $content);
                }

                $audit->log(
                        $adminId,
                        $adminName,
                        'ARCHIVE',
                        $table,
                        null,
                        "Архівовано {$rows} рядків у {$file}"
                );

                flashSet(
                        FlashType::Ok,
                        "Таблицю {$table} архівовано ({$rows} рядків) у {$file}"
                );

                break;
            }

            case 'new_period': {
                $start = (string) ($_POST['period_start'] ?? '');
                $end   = (string) ($_POST['period_end'] ?? '');
                $name  = (string) ($_POST['period_name'] ?? '');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) ||
                    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                    throw new \InvalidArgumentException('Дати облікового періоду некоректні.');
                }
                // 1) Створити резервну копію на межі періоду
                $snap = rtrim($backupDir, '/\\') . '/' . 'period_close_' . date('Ymd_His') . '.bak';
                $svc->backupDatabase($snap);
                // 2) Зберегти нові межі й назву
                $settings->set('accounting_period_start', $start);
                $settings->set('accounting_period_end',   $end);
                if ($name !== '') $settings->set('current_period_name', $name);
                // 3) Лог
                $audit->log($adminId, $adminName, 'PERIOD', 'settings', null,
                    "Перехід на новий період {$name} [{$start}..{$end}], snapshot: {$snap}");
                flashSet(FlashType::Ok, "Перехід на період «{$name}» виконано. Snapshot: {$snap}");
                break;
            }

            default:
                flashSet(FlashType::Error, 'Невідома операція.');
        }
    } catch (\Throwable $e) {
        $audit->log($adminId, $adminName, 'ERROR', null, null,
            "Сервіс ({$op}): " . $e->getMessage());
        flashSet(FlashType::Error, 'Помилка: ' . $e->getMessage());
    }

    redirect('/admin/service.php');
}

// ── GET ───────────────────────────────────────────────────────────────────────
$backupFiles  = $svc->listBackupFiles($backupDir);
$backupDirWarn = $svc->diagnoseBackupDir($backupDir);
$periodStart  = $settings->get('accounting_period_start', '');
$periodEnd    = $settings->get('accounting_period_end',   '');
$periodName   = $settings->get('current_period_name',     '');

$pageTitle    = 'Сервіс';
$activeAdmin  = 'service';

include __DIR__ . '/ui/partials/layout_head.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Сервіс</div>
    <div class="page-sub">
      Резервне копіювання, відновлення, архівація таблиць, перехід на новий період.
      Каталог резервних копій: <code><?= h($backupDir) ?></code>
    </div>
  </div>
</div>

<div class="warn-banner">
  <?= icon('warning') ?>
  <div>
    <strong>Увага — небезпечні операції.</strong>
    Відновлення БД <u>замінює поточні дані</u>. Перед цим створіть резервну копію.
    Кожна дія вимагає явного підтвердження та фіксується в Журналі дій.
  </div>
</div>

<?php if ($backupDirWarn !== null): ?>
  <div class="warn-banner">
    <?= icon('warning') ?>
    <div>
      <strong>Список існуючих бекапів недоступний.</strong>
      <?= h($backupDirWarn) ?>
      Створення нових бекапів може працювати (бо пише SQL Server, а не PHP),
      але dropdown «Відновлення БД» буде порожній.
    </div>
  </div>
<?php endif; ?>

<div class="svc-grid">

  <!-- ── BACKUP ────────────────────────────────────────────────────────────── -->
  <form method="post" class="card js-confirm-form" data-confirm="Створити резервну копію всієї БД?">
    <div class="card-title"><?= icon('download') ?> Резервна копія БД</div>
    <div class="card-sub">Повний BACKUP DATABASE у файл .bak на сервері СКБД.</div>
    <input type="hidden" name="op" value="backup">
    <label class="form-label" for="bfn">Ім'я файлу</label>
    <input id="bfn" class="input" type="text" name="file_name"
           placeholder="cowork_<?= date('Ymd_His') ?>.bak">
    <label class="checkline">
      <input type="checkbox" name="confirm" value="yes" required>
      <span>Я розумію, що файл буде перезаписано (<code>WITH INIT, FORMAT</code>).</span>
    </label>
    <button type="submit" class="btn btn-accent"><?= icon('download') ?> Створити копію</button>
  </form>

  <!-- ── RESTORE ───────────────────────────────────────────────────────────── -->
  <form method="post" class="card js-confirm-form"
        data-confirm="УВАГА! Відновлення повністю замінить поточну БД. Продовжити?">
    <div class="card-title"><?= icon('upload') ?> Відновлення БД</div>
    <div class="card-sub">RESTORE DATABASE з обраного файлу. Поточні дані будуть замінені.</div>
    <input type="hidden" name="op" value="restore">
    <label class="form-label" for="bf">Файл .bak</label>
    <select name="backup_file" id="bf" class="input" required>
      <option value="">— оберіть файл —</option>
      <?php foreach ($backupFiles as $f): ?>
        <option value="<?= h($f['name']) ?>">
          <?= h($f['name']) ?> — <?= number_format($f['size'] / 1024 / 1024, 1) ?> MB,
          <?= date('Y-m-d H:i', (int) $f['mtime']) ?>
        </option>
      <?php endforeach ?>
    </select>
    <label class="checkline danger">
      <input type="checkbox" name="confirm" value="yes" required>
      <span>Я підтверджую <strong>повну заміну</strong> поточної БД даними з архіву.</span>
    </label>
    <button type="submit" class="btn btn-danger"><?= icon('upload') ?> Відновити БД</button>
  </form>

  <!-- ── ARCHIVE TABLE ─────────────────────────────────────────────────────── -->
  <form method="post" class="card js-confirm-form" data-confirm="Архівувати вибрану таблицю у CSV?">
    <div class="card-title"><?= icon('archive') ?> Архівація таблиці (CSV)</div>
    <div class="card-sub">Експорт вмісту обраної таблиці у CSV-файл.</div>
    <input type="hidden" name="op" value="archive_table">
    <label class="form-label" for="at">Таблиця</label>
    <select name="archive_table" id="at" class="input" required>
      <?php foreach (['users','coworkings','workspaces','bookings','booking_slots',
                      'reviews','audit_log','settings'] as $t): ?>
        <option value="<?= h($t) ?>"><?= h($t) ?></option>
      <?php endforeach ?>
    </select>
    <label class="checkline">
      <input type="checkbox" name="confirm" value="yes" required>
      <span>Підтверджую архівацію у каталог бекапів.</span>
    </label>
    <button type="submit" class="btn"><?= icon('archive') ?> Архівувати</button>
  </form>

  <!-- ── NEW PERIOD ────────────────────────────────────────────────────────── -->
  <form method="post" class="card js-confirm-form"
        data-confirm="Виконати перехід на новий обліковий період? Буде створено snapshot поточного стану.">
    <div class="card-title"><?= icon('history') ?> Новий обліковий період</div>
    <div class="card-sub">
      Поточний: <strong><?= h($periodName) ?></strong>
      (<?= h($periodStart) ?> … <?= h($periodEnd) ?>).
      Створюється резервна копія та оновлюються налаштування.
    </div>
    <input type="hidden" name="op" value="new_period">
    <div class="row2">
      <div>
        <label class="form-label">Назва</label>
        <input class="input" type="text" name="period_name" value="<?= h((string) (date('Y') + 1)) ?>">
      </div>
      <div>
        <label class="form-label">Початок</label>
        <input class="input" type="date" name="period_start" value="<?= h(date('Y-01-01', strtotime('+1 year'))) ?>" required>
      </div>
      <div>
        <label class="form-label">Кінець</label>
        <input class="input" type="date" name="period_end" value="<?= h(date('Y-12-31', strtotime('+1 year'))) ?>" required>
      </div>
    </div>
    <label class="checkline">
      <input type="checkbox" name="confirm" value="yes" required>
      <span>Підтверджую перехід на новий обліковий період.</span>
    </label>
    <button type="submit" class="btn btn-accent"><?= icon('history') ?> Виконати перехід</button>
  </form>
</div>

<!-- ── Файли резервних копій ───────────────────────────────────────────────── -->
<div class="card" style="margin-top:1.25rem">
  <div class="card-title"><?= icon('archive') ?> Файли в каталозі бекапів</div>
  <?php if (!$backupFiles): ?>
    <div style="color:var(--text3)">Каталог порожній або недоступний.</div>
  <?php else: ?>
    <table class="tbl">
      <thead><tr><th>Файл</th><th>Розмір</th><th>Змінено</th></tr></thead>
      <tbody>
        <?php foreach ($backupFiles as $f): ?>
          <tr>
            <td><code><?= h($f['name']) ?></code></td>
            <td><?= number_format($f['size'] / 1024 / 1024, 2) ?> MB</td>
            <td><?= date('Y-m-d H:i:s', (int) $f['mtime']) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>
</div>

<style>
  .svc-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem }
  .card-title { font-family:var(--font-serif);font-size:var(--fs-lg);font-weight:600;display:flex;align-items:center;gap:.5rem;margin-bottom:.35rem }
  .card-sub   { font-size:var(--fs-sm);color:var(--text2);margin-bottom:.85rem }
  .form-label { display:block;font-size:var(--fs-xs);color:var(--text2);margin:.35rem 0 .25rem;font-weight:500 }
  .input { width:100%;padding:.45rem .6rem;font-size:var(--fs-sm);border:1px solid var(--border2);border-radius:var(--radius);background:var(--surface);color:var(--text);font-family:inherit }
  .checkline { display:flex;gap:.5rem;align-items:flex-start;margin:.75rem 0;font-size:var(--fs-sm);color:var(--text2);line-height:1.45 }
  .checkline.danger { color:var(--red);background:var(--red-lt);padding:.5rem .625rem;border-radius:var(--radius) }
  .row2 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem }
  .btn-danger { background:#B23A3A;color:#fff;border:none }
  .btn-danger:hover { background:#8F2D2D }
  .warn-banner { display:flex;gap:.75rem;align-items:flex-start;background:#FFF6E0;border:1px solid #E8CE85;color:#6A4A00;padding:.75rem .875rem;border-radius:var(--radius);margin-bottom:1rem }
  .tbl { width:100%;border-collapse:collapse;font-size:var(--fs-sm);margin-top:.5rem }
  .tbl th, .tbl td { padding:.45rem .65rem;text-align:left;border-bottom:1px solid var(--border) }
  .tbl th { font-weight:600;color:var(--text2);background:var(--bg2);font-size:var(--fs-xs);text-transform:uppercase;letter-spacing:.04em }
</style>

<script>
  // Подвійне підтвердження для небезпечних операцій у розділі «Сервіс».
  document.querySelectorAll('.js-confirm-form').forEach(f => {
    f.addEventListener('submit', e => {
      const msg = f.dataset.confirm || 'Підтвердити дію?';
      if (!confirm(msg)) e.preventDefault();
    });
  });
</script>

<?php include __DIR__ . '/ui/partials/layout_foot.php'; ?>
