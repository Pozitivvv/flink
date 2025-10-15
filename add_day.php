<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// ✅ AJAX видалення теми
if (isset($_POST['delete_id'])) {
    header('Content-Type: application/json');
    
    $delete_id = (int)$_POST['delete_id'];

    try {
        // Видаляємо слова, пов'язані з темою
        $pdo->prepare("DELETE FROM words WHERE day_id = ? AND user_id = ?")->execute([$delete_id, $user_id]);
        // Видаляємо саму тему
        $pdo->prepare("DELETE FROM days WHERE id = ? AND user_id = ?")->execute([$delete_id, $user_id]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ✅ Створення нової теми
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    if ($title !== '') {
        $stmt = $pdo->prepare("INSERT INTO days (user_id, title) VALUES (?, ?)");
        $stmt->execute([$user_id, $title]);
        $last_id = $pdo->lastInsertId();

        // Перенаправлення на додавання слів
        header("Location: add_word.php?day_id=$last_id");
        exit();
    } else {
        $message = '⚠️ Введіть назву теми або уроку.';
    }
}

// ✅ Отримуємо список всіх тем користувача
$stmt = $pdo->prepare("SELECT * FROM days WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додати день / тему | Німецький словник</title>
    <link rel="stylesheet" href="assets/add-day.css">
    <link rel="stylesheet" href="assets/main-style.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="dashboard.php" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </a>
            <h1>✍️ Додати день / тему</h1>
        </div>

        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="title" placeholder="Назва уроку / теми" required>
            <button type="submit">Створити та перейти</button>
        </form>

        <h3>🗓️ Ваші уроки / теми:</h3>

        <?php if ($days): ?>
            <ul class="theme-list" id="themeList">
                <?php foreach ($days as $day): ?>
                    <?php
                        // 🕒 Форматуємо дату створення (дд.мм.рр, гг:хх)
                        $formatted_date = date("d.m.y, H:i", strtotime($day['created_at']));
                    ?>
                    <li class="theme-item" data-id="<?= $day['id'] ?>" onclick="location.href='add_word.php?day_id=<?= $day['id'] ?>'">
                        <span>
                            <strong><?= htmlspecialchars($day['title']) ?></strong><br>
                            <small>📅 <?= $formatted_date ?></small>
                        </span>
                        <div class="actions">
                            <button class="delete-btn" onclick="event.stopPropagation();">🗑️ Видалити</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                <p>Ще не додано жодного уроку.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Нижнє меню (тільки для мобільних) -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="nav-item">
            <span>🏠</span>
            Головна
        </a>
        <a href="add_day.php" class="nav-item active">
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
            <span>👤</span>
            Профиль
        </a>
    </nav>

    <script src="script/add-day.js"></script>
</body>
</html>