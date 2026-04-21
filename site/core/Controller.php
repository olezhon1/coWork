<?php
// site/core/Controller.php

abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    protected function requireLogin(): void
    {
        if (!Auth::check()) {
            flash('warn', 'Увійдіть, щоб продовжити');
            Response::redirect(siteUrl('login', ['return' => $_SERVER['REQUEST_URI'] ?? '/']));
        }
    }
}
