<?php
// site/config/bootstrap.php
// Єдина точка ініціалізації публічного сайту

declare(strict_types=1);

// Підключаємо конфіг БД з адмінки (спільне підключення, одні константи)
require_once __DIR__ . '/../../admin/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Core
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';

// Моделі
require_once __DIR__ . '/../models/Db.php';
require_once __DIR__ . '/../models/CoworkingModel.php';
require_once __DIR__ . '/../models/WorkspaceModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../models/BookingSlotModel.php';
require_once __DIR__ . '/../models/ReviewModel.php';
require_once __DIR__ . '/../models/FeatureModel.php';
require_once __DIR__ . '/../models/GalleryModel.php';
require_once __DIR__ . '/../models/OperatingHoursModel.php';
require_once __DIR__ . '/../models/SubscriptionPlanModel.php';
require_once __DIR__ . '/../models/SubscriptionModel.php';

// Enum-и з адмінки — переUSE'ємо
require_once __DIR__ . '/../../admin/enums/WorkspaceType.php';
require_once __DIR__ . '/../../admin/enums/BookingStatus.php';
require_once __DIR__ . '/../../admin/enums/SubscriptionStatus.php';
require_once __DIR__ . '/../../admin/enums/UserRole.php';
require_once __DIR__ . '/../../admin/enums/GalleryEntityType.php';
