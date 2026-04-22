<?php
// config/bootstrap.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$enumFiles = [
    'AdminTable',
    'BookingStatus',
    'WorkspaceType',
    'GalleryEntityType',
    'FormFieldType',
    'FlashType',
    'WarnReason',
    'UserRole',
];
foreach ($enumFiles as $enum) {
    require_once __DIR__ . "/../enums/{$enum}.php";
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../assets/icons/icons.php';
require_once __DIR__ . '/../ui/table_config.php';

function requireAdmin(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['admin']);
}

function currentAdminRole(): ?UserRole
{
    $raw = $_SESSION['admin_role'] ?? null;
    return $raw ? UserRole::tryFrom((string)$raw) : null;
}

/** Гейт для системних розділів (Сервіс, Налаштування, Журнал дій). */
function requireSuperAdmin(): void
{
    requireAdmin();
    $role = currentAdminRole();
    if (!$role || !$role->isSuperAdmin()) {
        flashSet(FlashType::Error, 'Доступ лише для адміністратора.');
        header('Location: /admin/');
        exit;
    }
}

/** Гейт для таблиці в адмінці — відповідно до ролі. */
function requireTableAccess(AdminTable $t): void
{
    requireAdmin();
    $role = currentAdminRole();
    if (!$role || !$role->canAccessTable($t)) {
        flashSet(FlashType::Error, 'Немає прав для роботи з цією таблицею.');
        header('Location: /admin/');
        exit;
    }
}

function flashSet(FlashType $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type->value, 'msg' => $msg];
}

function flashGet(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}
