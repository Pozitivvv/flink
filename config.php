<?php

// --- НАСТРОЙКИ СЕССИИ ---
$session_lifetime = 60 * 60 * 24 * 7; // 7 дней
$custom_sess_path = __DIR__ . '/sessions';

// Создаем папку для сессий
if (!file_exists($custom_sess_path)) {
    mkdir($custom_sess_path, 0777, true);
    chmod($custom_sess_path, 0777);
}

// Устанавливаем параметры куки ДО session_start()
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(),
        $_COOKIE[session_name()],
        [
            'expires'  => time() + $session_lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false, // true если есть HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Запускаем сессию
session_start();

// Если это новая сессия - устанавливаем куку
if (!isset($_COOKIE[session_name()]) || $_COOKIE[session_name()] !== session_id()) {
    setcookie(
        session_name(),
        session_id(),
        [
            'expires'  => time() + $session_lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Копируем файл сессии в нашу папку (для резервного хранения)
$system_sess_file = session_save_path() . '/sess_' . session_id();
$custom_sess_file = $custom_sess_path . '/sess_' . session_id();
if (file_exists($system_sess_file)) {
    @copy($system_sess_file, $custom_sess_file);
}

// --- ПОДКЛЮЧЕНИЕ К БД ---
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
} catch (PDOException $e) {
    die("❌ Помилка підключення до БД: " . $e->getMessage());
}

?>