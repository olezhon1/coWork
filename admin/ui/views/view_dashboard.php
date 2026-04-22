<?php
// ui/views/view_dashboard.php

$dashRole = currentAdminRole() ?? UserRole::User;

$groupsAll = [
    'Користувачі' => [AdminTable::Users],
    'Каталог'     => [AdminTable::Coworkings, AdminTable::Workspaces, AdminTable::OperatingHours, AdminTable::Features, AdminTable::CoworkingFeatures, AdminTable::Gallery],
    'Сервіс'      => [AdminTable::Bookings, AdminTable::BookingSlots, AdminTable::Reviews],
];

$groups = [];
foreach ($groupsAll as $name => $tables) {
    $allowed = array_values(array_filter($tables, fn($t) => $dashRole->canAccessTable($t)));
    if ($allowed) $groups[$name] = $allowed;
}
?>
<div class="page-header">
  <div>
    <div class="page-title">Дашборд</div>
    <div class="page-sub">Огляд усіх даних системи</div>
  </div>
</div>

<?php foreach ($groups as $groupName => $tables): ?>
  <div class="dash-group-title"><?= h($groupName) ?></div>
  <div class="dash-grid">
    <?php foreach ($tables as $t): ?>
      <a href="/admin/?t=<?= h($t->value) ?>" class="dash-card">
        <div class="dash-icon"><?= icon($t->icon()) ?></div>
        <div class="dash-label"><?= h($t->label()) ?></div>
        <div class="dash-count"><?= h((string)($stats[$t->value] ?? '—')) ?></div>
      </a>
    <?php endforeach ?>
  </div>
<?php endforeach ?>
