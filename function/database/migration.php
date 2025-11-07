<?php
header('Content-Type: text/html; charset=utf-8');
/**
 * migrate.php — створює всі таблиці для застосунку "Німецький словник"
 * та додає колонки для системи ачивок
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

    // Встановлюємо правильну кодування для з’єднання
    $db->exec("SET NAMES utf8mb4");
    $db->exec("SET CHARACTER SET utf8mb4");
    $db->exec("SET COLLATION_CONNECTION = utf8mb4_unicode_ci");

    // Вимикаємо перевірку зовнішніх ключів для створення таблиць
    $db->exec("SET FOREIGN_KEY_CHECKS=0;");

    // 🧩 Таблиця користувачів
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            login VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            errors_fixed INT DEFAULT 0,
            days_active INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "🧩 Таблиця 'users' створена або вже існує.<br>";

    // 🔧 Перевіряємо наявність колонок для streak і first_login_done
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $alter = [];

    if (!in_array('last_active_date', $cols)) $alter[] = "ADD COLUMN last_active_date DATE DEFAULT NULL";
    if (!in_array('current_streak', $cols)) $alter[] = "ADD COLUMN current_streak INT DEFAULT 0";
    if (!in_array('first_login_done', $cols)) $alter[] = "ADD COLUMN first_login_done TINYINT(1) DEFAULT 0";

    if (!empty($alter)) {
        $db->exec("ALTER TABLE users " . implode(", ", $alter) . ";");
        echo "🔄 Колонки last_active_date, current_streak, first_login_done додані.<br>";
    } else {
        echo "✅ Усі необхідні колонки вже існують.<br>";
    }

    // 📘 Таблиця days
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

    // 🗣️ Таблиця words
    $db->exec("
        CREATE TABLE IF NOT EXISTS words (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            day_id INT NULL,
            article VARCHAR(20),
            german VARCHAR(255) NOT NULL,
            translation VARCHAR(255) NOT NULL,
            errors INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "🗣️ Таблиця 'words' створена або вже існує.<br>";

    // 🚫 Таблиця user_errors
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

    // 📖 Таблиця base_words
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

    // 🏆 Таблиця achievements
    $db->exec("
        CREATE TABLE IF NOT EXISTS achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            icon VARCHAR(255),
            condition_type VARCHAR(50),
            condition_value INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "🏆 Таблиця 'achievements' створена або вже існує.<br>";

    // 🎯 Таблиця user_achievements
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            achievement_id INT NOT NULL,
            achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_achievement (user_id, achievement_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "🎯 Таблиця 'user_achievements' створена або вже існує.<br>";

    // Повертаємо перевірку зовнішніх ключів
    $db->exec("SET FOREIGN_KEY_CHECKS=1;");

    echo "<br><br>✅ Міграція завершена успішно!";
} catch (PDOException $e) {
    echo "❌ Помилка міграції: " . $e->getMessage();
}
?>
