<?php
// admin/settings.php — налаштування системи (обліковий період, шляхи, тощо)

require_once __DIR__ . '/config/bootstrap.php';
requireAdmin();

require_once __DIR__ . '/db/SettingsRepository.php';
require_once __DIR__ . '/db/AuditLogRepository.php';

$repo  = new SettingsRepository();
$audit = new AuditLogRepository();

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST['settings'] ?? [];
    $changed = [];
    foreach ($posted as $key => $value) {
        $key = (string) $key;
        $value = trim((string) $value);
        $current = $repo->get($key);
        if ($current !== $value) {
            $repo->set($key, $value);
            $changed[] = "{$key}: «" . ($current ?? '—') . "» → «{$value}»";
        }
    }

    if ($changed) {
        $audit->log(
            (int) ($_SESSION['admin_id'] ?? 0),
            (string) ($_SESSION['admin_name'] ?? 'admin'),
            'SETTINGS',
            'settings',
            null,
            implode('; ', $changed),
        );
        flashSet(FlashType::Ok, 'Налаштування збережено (' . count($changed) . ').');
    } else {
        flashSet(FlashType::Info, 'Змін немає.');
    }
    redirect('/admin/settings.php');
}

$items       = $repo->all();
$pageTitle   = 'Налаштування';
$activeAdmin = 'settings';

include __DIR__ . '/ui/partials/layout_head.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Налаштування системи</div>
    <div class="page-sub">Обліковий період, шляхи до резервних копій і модулів, терміни зберігання журналу.</div>
  </div>
</div>

<form method="post" class="card">
  <?php foreach ($items as $it): ?>
    <?php
      $isDate = in_array($it['skey'], ['accounting_period_start', 'accounting_period_end'], true);
      $isNum  = $it['skey'] === 'data_retention_days';
      $type   = $isDate ? 'date' : ($isNum ? 'number' : 'text');
    ?>
    <div style="margin-bottom:1rem">
      <label class="form-label" for="s_<?= h($it['skey']) ?>">
        <?= h((string) ($it['label'] ?: $it['skey'])) ?>
        <code style="color:var(--text3);font-weight:400">(<?= h((string) $it['skey']) ?>)</code>
      </label>
      <input type="<?= $type ?>"
             id="s_<?= h($it['skey']) ?>"
             name="settings[<?= h($it['skey']) ?>]"
             value="<?= h((string) $it['svalue']) ?>"
             class="input"
             <?= $isNum ? 'min="0"' : '' ?>>
      <?php if ($it['description']): ?>
        <div style="font-size:var(--fs-xs);color:var(--text3);margin-top:.3rem"><?= h((string) $it['description']) ?></div>
      <?php endif ?>
      <?php if ($it['updated_at']): ?>
        <div style="font-size:var(--fs-xs);color:var(--text3);margin-top:.15rem">
          Оновлено: <?= h(substr((string) $it['updated_at'], 0, 19)) ?>
        </div>
      <?php endif ?>
    </div>
  <?php endforeach ?>

  <div style="display:flex;gap:.5rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
    <button type="submit" class="btn btn-accent">
      <?= icon('save') ?> Зберегти
    </button>
    <a href="/admin/settings.php" class="btn">Скасувати</a>
  </div>
</form>

<style>
  .form-label { display:block;font-size:var(--fs-sm);color:var(--text);margin-bottom:.3rem;font-weight:500 }
  .form-label code { font-size:var(--fs-xs) }
  .input { width:100%;padding:.5rem .7rem;font-size:var(--fs-sm);border:1px solid var(--border2);border-radius:var(--radius);background:var(--surface);color:var(--text);font-family:inherit }
</style>

<?php include __DIR__ . '/ui/partials/layout_foot.php'; ?>
