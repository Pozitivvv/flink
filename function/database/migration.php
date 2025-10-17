<?php
header('Content-Type: text/html; charset=utf-8');
/**
 * migrate.php — створює всі таблиці для застосунку "Німецький словник"
 */

try {
    // Підключення до бази даних із правильною кодуванням
    $db = new PDO(
        'mysql:host=localhost;dbname=flyca583_wortly;charset=utf8mb4', 
        'flyca583_wortly', 
        'wortlyCMD_',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );

    // Перевіримо підключення
    $db->query("SELECT 1");
    echo "✅ Підключення до бази даних успішне.<br>";

    // Устанавливаем правильну кодировку для соединения
    $db->exec("SET NAMES utf8mb4");
    $db->exec("SET CHARACTER SET utf8mb4");
    $db->exec("SET COLLATION_CONNECTION = utf8mb4_unicode_ci");

    // Вимикаємо перевірку зовнішніх ключів для створення таблиць
    $db->exec("SET FOREIGN_KEY_CHECKS=0;");

    // Таблиця користувачів
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            login VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "🧩 Таблиця 'users' створена або вже існує.<br>";

    // Таблиця days (уроки/теми)
    $db->exec("
        CREATE TABLE IF NOT EXISTS days (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "📘 Таблиця 'days' створена або вже існує.<br>";

    // Таблиця words (слова)
    $db->exec("
        CREATE TABLE IF NOT EXISTS words (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            day_id INT NULL,
            article VARCHAR(20),
            german VARCHAR(255) NOT NULL,
            translation VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "🗣️ Таблиця 'words' створена або вже існує.<br>";

    // Таблиця user_errors (помилки користувача)
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_errors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            word_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE,
            UNIQUE KEY uq_user_word (user_id, word_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "🚫 Таблиця 'user_errors' створена або вже існує.<br>";

    // Таблиця базових слів
    $db->exec("
        CREATE TABLE IF NOT EXISTS base_words (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article VARCHAR(20),
            german VARCHAR(255) NOT NULL,
            transcription VARCHAR(255),
            translation VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "📖 Таблиця 'base_words' створена або вже існує.<br>";

    // Увімкнути перевірку зовнішніх ключів
    $db->exec("SET FOREIGN_KEY_CHECKS=1;");

    echo "<br>✅ Міграція завершена успішно!";
} catch (PDOException $e) {
    echo "❌ Помилка міграції: " . $e->getMessage();
}
?>
