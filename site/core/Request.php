<?php
// site/core/Request.php

class Request
{
    public static function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = $_GET[$key] ?? $_POST[$key] ?? null;
        return $v !== null && $v !== '' ? (int) $v : $default;
    }

    public static function str(string $key, string $default = ''): string
    {
        $v = $_GET[$key] ?? $_POST[$key] ?? null;
        return $v !== null ? trim((string) $v) : $default;
    }

    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function page(): string
    {
        return (string) ($_GET['page'] ?? 'home');
    }

    public static function action(): string
    {
        return (string) ($_GET['action'] ?? 'index');
    }
}
