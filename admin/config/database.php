<?php
// config/database.php

define('DB_HOST',   'localhost');
define('DB_NAME',   'CoworkingDB');
define('DB_USER',   'Oleh');
define('DB_PASS',   'password');
define('DB_PORT',   '1433');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "sqlsrv:Server=" . DB_HOST . "," . DB_PORT . ";Database=" . DB_NAME . ";TrustServerCertificate=1";

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Помилка підключення: " . $e->getMessage());
        }
    }
    return $pdo;
}