<?php
session_start();
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/config.php';

check_admin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image = trim($_POST['image'] ?? '');

    if ($title === '') {
        $message = '<div class="message error">❌ Назва обовʼязкова</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO modules (title, description, image) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $image]);

        header("Location: ?page=modules&created=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Додати модуль</title>
    <link rel="stylesheet" href="assets/main-style.css">
</head>
<body>

<div class="container">

    <div class="greeting">
        <div class="greeting-content">
            <div class="greeting-icon">➕</div>
            <div class="greeting-text">
                <h1>Створити новий модуль</h1>
            </div>
        </div>
        <a href="?page=modules" class="back-link">← Назад</a>
    </div>

    <?= $message ?>

    <div class="form-card">
        <form method="POST">
            <div class="form-group">
                <label>Назва модуля *</label>
                <input type="text" name="title" required>
            </div>

            <div class="form-group">
                <label>Опис</label>
                <textarea name="description"></textarea>
            </div>

            <div class="form-group">
                <label>URL зображення</label>
                <input type="text" name="image" placeholder="https://example.com/image.jpg">
            </div>

            <button type="submit" class="btn-primary">✅ Створити</button>
        </form>
    </div>

</div>

</body>
</html>
