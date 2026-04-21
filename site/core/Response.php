<?php
// site/core/Response.php

class Response
{
    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '/site/index.php';
        self::redirect($ref);
    }

    public static function notFound(string $message = 'Сторінку не знайдено'): void
    {
        http_response_code(404);
        echo '<h1>404</h1><p>' . htmlspecialchars($message) . '</p>';
        exit;
    }
}
