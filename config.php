<?php

$session_lifetime = 60 * 60 * 24 * 10;

ini_set('session.gc_maxlifetime', $session_lifetime);
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DB_HOST = 'localhost';          // або хост з панелі (іноді не localhost)
$DB_NAME = 'flink';   // назва бази
$DB_USER = 'root';   // ім'я користувача MySQL
$DB_PASS = 'root';        // ✅ це твій пароль

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

    // Виставляємо правильну кодування
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET COLLATION_CONNECTION = utf8mb4_unicode_ci");

} catch (PDOException $e) {
    die("❌ Помилка підключення до БД: " . $e->getMessage());
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

function current_user() {
    return $_SESSION['user_name'] ?? null;
}
?>