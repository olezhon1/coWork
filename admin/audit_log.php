<?php
// admin/audit_log.php — перегляд журналу дій адміністратором

require_once __DIR__ . '/config/bootstrap.php';
requireAdmin();

require_once __DIR__ . '/db/AuditLogRepository.php';
require_once __DIR__ . '/db/UserRepository.php';

$repo = new AuditLogRepository();

// ── Фільтри з GET ─────────────────────────────────────────────────────────────
$filters = [];
foreach (['action', 'table_name', 'user_id', 'date_from', 'date_to', 'search'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') {
        $filters[$k] = trim((string) $_GET[$k]);
    }
}

$page    = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 25;

$data    = $repo->findPaged($filters, $page, $perPage);
$rows    = $data['rows'];
$total   = $data['total'];
$pages   = max(1, (int) ceil($total / $perPage));

$actions = $repo->distinctActions();
$tables  = $repo->distinctTables();
$users   = $repo->activeUsers();

$pageTitle   = 'Журнал дій';
$activeAdmin = 'audit_log';

include __DIR__ . '/ui/partials/layout_head.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Журнал дій користувачів</div>
    <div class="page-sub">Усього записів: <strong><?= (int) $total ?></strong></div>
  </div>
</div>

<!-- ── Фільтри ─────────────────────────────────────────────────────────────── -->
<form method="get" action="/admin/audit_log.php" class="card" style="margin-bottom:1rem">
  <div style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.75rem">
    <div>
      <label class="form-label">Тип дії</label>
      <select name="action" class="input">
        <option value="">Усі</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?= h($a) ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>><?= h($a) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label class="form-label">Таблиця</label>
      <select name="table_name" class="input">
        <option value="">Усі</option>
        <?php foreach ($tables as $t): ?>
          <option value="<?= h($t) ?>" <?= ($filters['table_name'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label class="form-label">Користувач</label>
      <select name="user_id" class="input">
        <option value="">Усі</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>"
            <?= (int) ($filters['user_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>>
            <?= h($u['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label class="form-label">Від</label>
      <input type="date" name="date_from" class="input" value="<?= h($filters['date_from'] ?? '') ?>">
    </div>
    <div>
      <label class="form-label">До</label>
      <input type="date" name="date_to" class="input" value="<?= h($filters['date_to'] ?? '') ?>">
    </div>
    <div>
      <label class="form-label">Пошук у деталях</label>
      <input type="text" name="search" class="input"
             placeholder="текст або ім'я"
             value="<?= h($filters['search'] ?? '') ?>">
    </div>
  </div>
  <div style="display:flex;gap:.5rem;margin-top:.875rem">
    <button type="submit" class="btn btn-accent">
      <?= icon('search') ?> Застосувати
    </button>
    <a href="/admin/audit_log.php" class="btn">Скинути</a>
  </div>
</form>

<!-- ── Таблиця результатів ─────────────────────────────────────────────────── -->
<?php if (!$rows): ?>
  <div class="card" style="text-align:center;color:var(--text3);padding:2rem">
    Журнал порожній або за вказаними фільтрами нічого не знайдено.
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>ID</th>
          <th>Час</th>
          <th>Користувач</th>
          <th>Дія</th>
          <th>Таблиця</th>
          <th>ID запису</th>
          <th>Деталі</th>
          <th>IP</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int) $r['id'] ?></td>
            <td><?= h(substr((string) $r['created_at'], 0, 19)) ?></td>
            <td>
              <?php if ($r['user_id']): ?>
                <?= h((string) ($r['user_name'] ?: 'User #' . $r['user_id'])) ?>
                <span style="color:var(--text3);font-size:var(--fs-xs)">#<?= (int) $r['user_id'] ?></span>
              <?php else: ?>
                <span style="color:var(--text3)">—</span>
              <?php endif ?>
            </td>
            <td><span class="chip chip-<?= strtolower(h((string) $r['action'])) ?>"><?= h((string) $r['action']) ?></span></td>
            <td><?= h((string) ($r['table_name'] ?? '')) ?></td>
            <td><?= $r['record_id'] !== null ? (int) $r['record_id'] : '—' ?></td>
            <td style="max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                title="<?= h((string) ($r['details'] ?? '')) ?>">
              <?= h((string) ($r['details'] ?? '')) ?>
            </td>
            <td style="color:var(--text3);font-size:var(--fs-xs)"><?= h((string) ($r['ip_address'] ?? '')) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <!-- Пагінація -->
  <?php if ($pages > 1):
      $q = $_GET; unset($q['p']);
      $baseQs = http_build_query($q);
      $baseQs = $baseQs ? '&' . $baseQs : '';
  ?>
    <div class="pager" style="margin-top:1rem">
      <?php for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
        <a href="/admin/audit_log.php?p=<?= $i ?><?= $baseQs ?>"
           class="btn btn-sm <?= $i === $page ? 'btn-accent' : '' ?>"><?= $i ?></a>
      <?php endfor ?>
      <span style="color:var(--text3);margin-left:.75rem">Стор. <?= $page ?> з <?= $pages ?></span>
    </div>
  <?php endif ?>
<?php endif ?>

<style>
  .chip { display:inline-block;padding:.15rem .5rem;border-radius:999px;font-size:var(--fs-xs);background:var(--bg2);color:var(--text2);font-weight:500 }
  .chip-login, .chip-logout         { background:#E9F1EC;color:#2F6B55 }
  .chip-insert                      { background:#E6F0FA;color:#2A5A8A }
  .chip-update                      { background:#FFF3DC;color:#8A6400 }
  .chip-delete                      { background:#FBE2E2;color:#B23A3A }
  .chip-backup, .chip-restore       { background:#EEE6FA;color:#5A3AA8 }
  .chip-settings                    { background:#F2F4E6;color:#5A6B2F }
  .tbl { width:100%;border-collapse:collapse;font-size:var(--fs-sm) }
  .tbl th, .tbl td { padding:.55rem .75rem;text-align:left;border-bottom:1px solid var(--border) }
  .tbl th { font-weight:600;color:var(--text2);background:var(--bg2);font-size:var(--fs-xs);text-transform:uppercase;letter-spacing:.04em }
  .tbl tr:hover td { background:var(--bg2) }
  .table-wrap { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:auto }
  .form-label { display:block;font-size:var(--fs-xs);color:var(--text2);margin-bottom:.3rem;font-weight:500 }
  .input { width:100%;padding:.45rem .6rem;font-size:var(--fs-sm);border:1px solid var(--border2);border-radius:var(--radius);background:var(--surface);color:var(--text);font-family:inherit }
  .pager a { margin-right:.25rem }
</style>

<?php include __DIR__ . '/ui/partials/layout_foot.php'; ?>
