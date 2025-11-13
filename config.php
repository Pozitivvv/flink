<?php

$session_lifetime = 60 * 60 * 24 * 7;

ini_set('session.gc_maxlifetime', $session_lifetime);
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'flink';
$DB_USER = 'root';
$DB_PASS = 'root';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );

    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET COLLATION_CONNECTION = utf8mb4_unicode_ci");

} catch (PDOException $e) {
    die("❌ Помилка підключення до БД: " . $e->getMessage());
}

?>