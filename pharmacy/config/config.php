<?php
session_start();

// Base URL for links. Change this if you host the app in a different folder.
define('BASE_URL', '/pharmacy');

define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session timeout: 30 minutes
define('SESSION_TIMEOUT', 1800);

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $exception) {
    die('Database connection failed: ' . $exception->getMessage());
}
