<?php
// ui/partials/layout_head.php

$flash   = flashGet();
$cssVars = require __DIR__ . '/../../assets/css/variables.php';

$navGroups = [
    'users'   => ['label' => 'Користувачі', 'tables' => [AdminTable::Users]],
    'catalog' => ['label' => 'Каталог',     'tables' => [
        AdminTable::Coworkings, AdminTable::Workspaces,
        AdminTable::OperatingHours, AdminTable::Features,
        AdminTable::CoworkingFeatures, AdminTable::Gallery,
    ]],
    'service' => ['label' => 'Операції',     'tables' => [
        AdminTable::Bookings, AdminTable::BookingSlots,
        AdminTable::Reviews,
    ]],
];

// Додаткові системні посилання (не таблиці, окремі сторінки)
$adminLinks = [
    ['url' => '/admin/audit_log.php', 'icon' => 'history',  'label' => 'Журнал дій'],
    ['url' => '/admin/service.php',   'icon' => 'database', 'label' => 'Сервіс'],
    ['url' => '/admin/settings.php',  'icon' => 'settings', 'label' => 'Налаштування'],
];
$activeAdmin = $activeAdmin ?? null; // 'audit' | 'service' | 'settings' | null
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle ?? 'Адмінка') ?> — CoWork Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Serif:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/shared/css/tokens.css">
  <style><?= $cssVars ?></style>
  <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>

<header class="topbar">
  <a href="/admin/" class="logo">Co<em>Work</em><span class="logo-badge">ADMIN</span></a>
  <div class="top-right">
    <span class="top-user">
      <?= icon('user') ?>
      <?= h($_SESSION['admin_name'] ?? 'Admin') ?>
    </span>
    <form method="post" action="/admin/logout.php" style="margin:0">
      <button type="submit" class="btn btn-sm btn-ghost">
        <?= icon('logout') ?> Вийти
      </button>
    </form>
  </div>
</header>

<div class="admin-layout">

  <aside class="sidebar">
    <div class="sn-sect">Огляд</div>
    <a href="/admin/" class="sn-item <?= empty($activeTable) ? 'active' : '' ?>">
      <?= icon('dashboard') ?> Дашборд
    </a>

    <?php foreach ($navGroups as $group): ?>
      <div class="sn-sect"><?= h($group['label']) ?></div>
      <?php foreach ($group['tables'] as $t): ?>
        <a href="/admin/?t=<?= h($t->value) ?>"
           class="sn-item <?= isset($activeTable) && $activeTable === $t ? 'active' : '' ?>">
          <?= icon($t->icon()) ?> <?= h($t->label()) ?>
        </a>
      <?php endforeach ?>
    <?php endforeach ?>

    <div class="sn-sect">Адміністрування</div>
    <?php foreach ($adminLinks as $lnk):
        $slug = basename($lnk['url'], '.php');
        $isActive = ($activeAdmin === $slug) ||
                    ($activeAdmin === 'audit' && $slug === 'audit_log');
    ?>
      <a href="<?= h($lnk['url']) ?>" class="sn-item <?= $isActive ? 'active' : '' ?>">
        <?= icon($lnk['icon']) ?> <?= h($lnk['label']) ?>
      </a>
    <?php endforeach ?>
  </aside>

  <main class="main">

    <?php if ($flash):
        $ft = FlashType::tryFrom($flash['type']) ?? FlashType::Info;
    ?>
      <div class="alert <?= h($ft->alertClass()) ?>" id="js-flash">
        <?= icon($ft->iconName()) ?>
        <span><?= h($flash['msg']) ?></span>
      </div>
    <?php endif ?>
