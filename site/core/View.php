<?php
// site/core/View.php

class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if ($layout === '' || $layout === null) {
            echo $content;
            return;
        }

        $layoutFile = __DIR__ . '/../views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout not found: {$layout}");
        }
        $title = $data['title'] ?? 'coWork — бронювання коворкінгів';
        include $layoutFile;
    }

    public static function partial(string $partial, array $data = []): void
    {
        $file = __DIR__ . '/../views/partials/' . $partial . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Partial not found: {$partial}");
        }
        extract($data, EXTR_SKIP);
        include $file;
    }

    public static function component(string $component, array $data = []): void
    {
        $file = __DIR__ . '/../views/components/' . $component . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Component not found: {$component}");
        }
        extract($data, EXTR_SKIP);
        include $file;
    }
}
