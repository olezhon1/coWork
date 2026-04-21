<?php
// site/core/helpers.php

function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(array $params = []): string
{
    $base = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    if (empty($params)) return $base;
    return $base . '?' . http_build_query($params);
}

function siteUrl(string $page, array $params = []): string
{
    $base = '/site/index.php'; // може бути перевизначено через BASE_URL у конфігу
    $q = array_merge(['page' => $page], $params);
    return $base . '?' . http_build_query($q);
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}

function csrfCheck(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        echo 'CSRF validation failed';
        exit;
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function popFlashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function selectedCity(): ?string
{
    return $_SESSION['city'] ?? ($_COOKIE['city'] ?? null);
}

function setSelectedCity(?string $city): void
{
    if ($city === null || $city === '') {
        unset($_SESSION['city']);
        setcookie('city', '', time() - 3600, '/');
        return;
    }
    $_SESSION['city'] = $city;
    setcookie('city', $city, time() + 60 * 60 * 24 * 30, '/');
}

function formatPrice(float $v): string
{
    return number_format($v, 2, ',', ' ') . ' ₴';
}

function formatDateTime(?string $dt): string
{
    if (!$dt) return '';
    $ts = strtotime($dt);
    if (!$ts) return e($dt);
    return date('d.m.Y H:i', $ts);
}

function formatDate(?string $dt): string
{
    if (!$dt) return '';
    $ts = strtotime($dt);
    if (!$ts) return e($dt);
    return date('d.m.Y', $ts);
}

/**
 * Визначає чи коворкінг відкритий зараз (за operating_hours).
 * @param array $hoursRows рядки з operating_hours, відсортовані по day_of_week
 */
function isOpenNow(array $hoursRows, bool $is247 = false): bool
{
    if ($is247) return true;
    $nowDay = (int) date('N'); // 1=Mon..7=Sun
    $nowTime = date('H:i:s');
    foreach ($hoursRows as $h) {
        if ((int) $h['day_of_week'] === $nowDay) {
            if (!empty($h['is_closed'])) return false;
            return ($nowTime >= ($h['open_time'] ?? '00:00:00'))
                && ($nowTime <= ($h['close_time'] ?? '23:59:59'));
        }
    }
    return false;
}

function workspaceTypeLabel(string $key): string
{
    $t = WorkspaceType::tryFrom($key);
    return $t ? $t->label() : $key;
}

function bookingStatusLabel(string $key): string
{
    $s = BookingStatus::tryFrom($key);
    return $s ? $s->label() : $key;
}

function bookingStatusBadge(string $key): string
{
    $s = BookingStatus::tryFrom($key);
    return $s ? $s->badgeClass() : 'b-gray';
}
