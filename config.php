<?php
/**
 * config.php — підключення до бази даних з правильними параметрами
 */

$DB_HOST = 'localhost';          // або хост з панелі (іноді не localhost)
$DB_NAME = 'flyca583_wortly';   // назва бази
$DB_USER = 'flyca583_wortly';   // ім’я користувача MySQL
$DB_PASS = 'wortlyCMD_';        // ✅ це твій пароль

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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
