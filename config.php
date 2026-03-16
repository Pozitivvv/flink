<?php
// --- ПОДКЛЮЧЕНИЕ К БД ---
// $DB_HOST = 'localhost';
// $DB_NAME = 'flink';
// $DB_USER = 'root';
// $DB_PASS = 'root';

// --- ПОДКЛЮЧЕНИЕ К БД ---
$DB_HOST = 'localhost';
$DB_NAME = 'flyca583_wortly';
$DB_USER = 'flyca583_wortly';
$DB_PASS = 'wortlyCMD_';
// --- НАСТРОЙКИ СЕССИИ ---
$session_lifetime = 60 * 60 * 24 * 7; // 7 дней
$custom_sess_path = __DIR__ . '/sessions';

// Создаём папку для сессий если нет
if (!file_exists($custom_sess_path)) {
    mkdir($custom_sess_path, 0755, true);
}

// Указываем PHP хранить сессии в нашей папке
// И настраиваем GC (сборщик мусора) ДО session_start()
session_save_path($custom_sess_path);

ini_set('session.gc_maxlifetime',  $session_lifetime); // файл живёт 7 дней
ini_set('session.gc_probability',  1);                 // GC запускается с вероятностью 1/100
ini_set('session.gc_divisor',      100);               // при каждом 100-м запросе

// Настройка куки сессии ДО session_start()
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false, // поменяйте на true когда будет HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Запускаем сессию
session_start();

// Обновляем куку при каждом запросе (продлеваем срок)
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(),
        session_id(),
        [
            'expires'  => time() + $session_lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

// --- РАЗОВАЯ РУЧНАЯ ЧИСТКА СТАРЫХ ФАЙЛОВ (5% запросов) ---
// Дополнительная страховка поверх встроенного GC
if (mt_rand(1, 20) === 1) {
    $now = time();
    foreach (glob($custom_sess_path . '/sess_*') as $file) {
        if (is_file($file) && ($now - filemtime($file)) > $session_lifetime) {
            @unlink($file);
        }
    }
}


try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]
    );
} catch (PDOException $e) {
    die("❌ Помилка підключення до БД: " . $e->getMessage());
}

// === ЗАГРУЖАЕМ ЯЗЫК ===
if (isset($_SESSION['user_id'])) {
    // Теперь мы всегда берем свежий язык из БД
    $stmtLang = $pdo->prepare("SELECT learning_language FROM users WHERE id = ?");
    $stmtLang->execute([$_SESSION['user_id']]);
    $userData = $stmtLang->fetch(PDO::FETCH_ASSOC);

    $_SESSION['learning_language'] = !empty($userData['learning_language']) ? $userData['learning_language'] : 'de';
}
