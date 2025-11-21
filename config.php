<?php

// --- НАСТРОЙКИ СЕССИИ (Начало) ---
$session_lifetime = 60 * 60 * 24 * 7; // 7 дней

// 1. Определяем папку для хранения сессий рядом со скриптом
$sess_save_path = __DIR__ . '/sessions';

// 2. Если папки нет — создаем её автоматически (права 0755)
if (!file_exists($sess_save_path)) {
    mkdir($sess_save_path, 0755, true);
}

// 3. Говорим PHP сохранять файлы именно сюда
session_save_path($sess_save_path);

// 4. Настраиваем время жизни и сборщик мусора (GC)
// Теперь PHP будет сам чистить эту папку, удаляя файлы старше 7 дней
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path'     => '/',
    'domain'   => '.wortly.one', 
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
// --- НАСТРОЙКИ СЕССИИ (Конец) ---


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

    // Эти команды дублируют MYSQL_ATTR_INIT_COMMAND, но для надежности можно оставить
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET COLLATION_CONNECTION = utf8mb4_unicode_ci");

} catch (PDOException $e) {
    die("❌ Помилка підключення до БД: " . $e->getMessage());
}

?>