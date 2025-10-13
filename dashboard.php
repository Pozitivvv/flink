<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Получаем email пользователя для приветствия
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Меню | Німецький словник</title>
    <link rel="stylesheet" href="assets/dashboard.css">
    <link rel="stylesheet" href="assets/main-style.css">
</head>
<body>
    <div class="container">
        <h1>👋 Привіт, <?= htmlspecialchars($user['email']) ?>!</h1>
        <h3>Що хочеш зробити сьогодні?</h3>

        <div class="menu">
            <a href="add_day.php" class="menu-item">📘 Додати день / урок / тему</a>
            <a href="add_word.php" class="menu-item">✍️ Додати слово</a>
            <a href="flashcard/flashcards.php" class="menu-item">🧠 Тестування</a>
            <a href="dictionary.php" class="menu-item">📚 Словарь</a>
        </div>
    </div>

    <nav class="bottom-nav">
            <a href="dashboard.php" class="nav-item active">
                <span>🏠</span>
                Головна
            </a>
            <a href="add_day.php" class="nav-item ">
                <span>📘</span>
                Теми
            </a>
            <a href="dictionary.php" class="nav-item">
                <span>📚</span>
                Словарь
            </a>
            <a href="flashcard/flashcards.php" class="nav-item">
                <span>✏️</span>
                Практика
            </a>
            <a href="settings.php" class="nav-item">
                <span>⚙️</span>
                Налаштування
            </a>
    </nav>

    
</body>
</html>
