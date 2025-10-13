<?php
// config.php
// Конфигурация проекта и подключение к MySQL

// Настройки подключения
$DB_HOST = 'localhost';   // или адрес сервера MySQL
$DB_NAME = 'flink'; // имя базы данных
$DB_USER = 'root';        // пользователь MySQL
$DB_PASS = 'root';            // пароль (укажи свой)

// Подключение к базе
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Запускаем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверка авторизации
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

// Быстрая функция получения пользователя
function current_user() {
    return $_SESSION['user_name'] ?? null;
}
?>
