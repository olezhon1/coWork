<?php
require_once __DIR__ . '/config/bootstrap.php';

if (!empty($_SESSION['admin_id'])) {
    require_once __DIR__ . '/db/AuditLogRepository.php';
    (new AuditLogRepository())->log(
        (int) $_SESSION['admin_id'],
        (string) ($_SESSION['admin_name'] ?? 'admin'),
        'LOGOUT',
        null,
        null,
        'Адмін-вихід',
    );
}

session_destroy();
redirect('/admin/login.php');
