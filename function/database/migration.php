<?php
/**
 * migrate.php — создаёт все таблицы для приложения "Німецький словник"
 */

require_once '../../config.php';

try {
    // Проверим соединение
    $pdo->query("SELECT 1");
    echo "✅ Підключення до бази даних успішне.<br>";

    // Создание таблицы users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "🧩 Таблиця 'users' створена або вже існує.<br>";

    // Создание таблицы days (уроки/теми)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS days (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");
    echo "📘 Таблиця 'days' створена або вже існує.<br>";

    // Создание таблицы words (слова)
    $pdo->exec("
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
        );
    ");
    echo "🗣️ Таблиця 'words' створена або вже існує.<br>";
    
    &pdo->exec ("
        CREATE TABLE IF NOT EXISTS user_errors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            word_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE,
            UNIQUE KEY uq_user_word (user_id, word_id)
        );
    ");
    echo "🚫 Таблиця 'user_errors' створена або вже існує.<br>";

    echo "<br>✅ Міграція завершена успішно!";
} catch (PDOException $e) {
    echo "❌ Помилка міграції: " . $e->getMessage();
}
