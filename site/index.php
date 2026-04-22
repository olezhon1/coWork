<?php
// site/index.php — фронт-контролер публічного сайту

require_once __DIR__ . '/config/bootstrap.php';

// Автозавантаження контролерів за назвою класу
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/controllers/' . $class . '.php';
    if (is_file($file)) require_once $file;
});

$page   = Request::page();
$action = Request::action();
$isPost = Request::isPost();

// Карта роутів: page => [GET controller@action, POST controller@action]
$routes = [
    'home' => [
        'GET'  => [HomeController::class, 'index'],
    ],
    'coworkings' => [
        'GET'  => [CoworkingsController::class, 'index'],
    ],
    'coworking' => [
        'GET'  => [CoworkingController::class, 'show'],
    ],
    'book' => [
        'GET'  => [BookingController::class, 'form'],
        'POST' => [BookingController::class, 'create'],
    ],
    'login' => [
        'GET'  => [AuthController::class, 'loginForm'],
        'POST' => [AuthController::class, 'login'],
    ],
    'register' => [
        'GET'  => [AuthController::class, 'registerForm'],
        'POST' => [AuthController::class, 'register'],
    ],
    'logout' => [
        'GET'  => [AuthController::class, 'logout'],
    ],
    'profile' => [
        'GET'  => [ProfileController::class, 'index'],
    ],
    'profile_update' => [
        'POST' => [ProfileController::class, 'update'],
    ],
    'profile_password' => [
        'POST' => [ProfileController::class, 'updatePassword'],
    ],
    'cancel_booking' => [
        'POST' => [ProfileController::class, 'cancelBooking'],
    ],
    'review' => [
        'POST' => [ReviewController::class, 'create'],
    ],
    'set_city' => [
        'GET'  => [CityController::class, 'set'],
        'POST' => [CityController::class, 'set'],
    ],
];

$method = $isPost ? 'POST' : 'GET';
$route = $routes[$page][$method] ?? null;

if (!$route) {
    // Якщо сторінка існує, але для іншого методу — допустити GET як fallback
    if (isset($routes[$page]['GET'])) {
        $route = $routes[$page]['GET'];
    } else {
        Response::notFound('Сторінку не знайдено: ' . htmlspecialchars($page));
    }
}

[$controllerClass, $actionMethod] = $route;
$controller = new $controllerClass();
$controller->$actionMethod();
