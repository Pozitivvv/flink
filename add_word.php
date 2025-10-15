<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// ✅ AJAX видалення слова
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    // Перевіряємо, чи слово належить поточному користувачу
    $check = $pdo->prepare("SELECT id FROM words WHERE id = ? AND user_id = ?");
    $check->execute([$delete_id, $user_id]);

    if ($check->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM words WHERE id = ? AND user_id = ?");
        $stmt->execute([$delete_id, $user_id]);
        echo "success";
    } else {
        echo "error";
    }
    exit; // 🔥 важливо для AJAX — не вантажимо HTML
}

// Получаем ID темы, если передан
$day_id = isset($_GET['day_id']) ? (int)$_GET['day_id'] : null;

// Получаем список всех тем пользователя
$stmt = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Добавление слова
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['german'])) {
    $selected_day = $_POST['day_id'] !== '' ? (int)$_POST['day_id'] : null;
    $article = trim($_POST['article']);
    $german = trim($_POST['german']);
    $translation = trim($_POST['translation']);

    if ($german !== '' && $translation !== '') {
        // Проверка, есть ли уже такое слово у пользователя
        $check = $pdo->prepare("SELECT id FROM words WHERE user_id = ? AND german = ?");
        $check->execute([$user_id, $german]);
        if ($check->fetch()) {
            $message = "⚠️ Це слово вже є у вашому словнику.";
        } else {
            // Добавляем слово
            $stmt = $pdo->prepare("
                INSERT INTO words (user_id, day_id, article, german, translation)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $selected_day, $article, $german, $translation]);
            $message = "✅ Слово <b>" . htmlspecialchars($german) . "</b> додано!";
            if ($selected_day) $day_id = $selected_day;
        }
    } else {
        $message = '⚠️ Заповніть усі поля.';
    }
}

// Если выбрана тема — показываем слова только из неё
$words = [];
if ($day_id) {
    $stmt = $pdo->prepare("
        SELECT id, german, article, translation
        FROM words WHERE user_id = ? AND day_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id, $day_id]);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->prepare("SELECT title FROM days WHERE id = ? AND user_id = ?");
    $stmt2->execute([$day_id, $user_id]);
    $current_day = $stmt2->fetchColumn();
} else {
    $current_day = null;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додати слова | Німецький словник</title>
    <link rel="stylesheet" href="assets/add-word.css">
    <link rel="stylesheet" href="assets/main-style.css">
</head>
<body>
    <div class="container">

    <div class="page-header">
        <a href="#" class="back-btn" onclick="goBack()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </a>
        <h1>✍️ Додати слово</h1>
    </div>

        <?php if ($message): ?>
            <p class="message" style="color:<?= str_contains($message, '✅') ? 'green' : 'red' ?>;">
                <?= $message ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <label for="day_id">Оберіть тему (необов'язково):</label>
            <select name="day_id" id="day_id">
                <option value="">— Без теми —</option>
                <?php foreach ($days as $day): ?>
                    <option value="<?= $day['id'] ?>" <?= ($day_id == $day['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($day['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="article" placeholder="Артикль (der, die, das...) — необов'язково">
            <input type="text" name="german" placeholder="Німецьке слово" required>
            <input type="text" name="translation" placeholder="Переклад" required>
            <button type="submit">Додати слово</button>
        </form>

        <?php if ($current_day): ?>
            <h3 style="margin-top:30px;">📘 Слова теми: «<?= htmlspecialchars($current_day) ?>»</h3>

            <?php if ($words): ?>
                <div class="audio-hint">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                        <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                    </svg>
                    Натисніть на слово, щоб прослухати вимову
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($words): ?>
            <table>
                <tr>
                    <th>Артикль</th>
                    <th>Слово</th>
                    <th>Переклад</th>
                    <th></th>
                </tr>
                <?php foreach ($words as $word): 
                    $fullWord = trim(($word['article'] ? $word['article'] . ' ' : '') . $word['german']);
                ?>
                    <tr id="word-<?= $word['id'] ?>">
                        <td><?= htmlspecialchars($word['article']) ?></td>
                        <td class="word-cell" data-word="<?= htmlspecialchars($fullWord) ?>">
                            <b><?= htmlspecialchars($word['german']) ?></b>
                        </td>
                        <td><?= htmlspecialchars($word['translation']) ?></td>
                        <td>
                            <button class="delete-btn" data-id="<?= $word['id'] ?>">🗑️</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php elseif ($current_day): ?>
            <p style="color:#7f8c8d;margin-top:20px;">Поки що немає слів у цій темі.</p>
        <?php endif; ?>
    
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
                <span>👤</span>
                Профиль
            </a>
    </nav>

    <!-- Модальное окно удаления -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">🗑️</div>
                <h2>Видалити слово?</h2>
                <p>Цю дію не можна буде скасувати</p>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" id="cancelDelete">Скасувати</button>
                <button class="modal-btn modal-btn-delete" id="confirmDelete">Видалити</button>
            </div>
        </div>
    </div>

    <script src="script/add-word.js"></script>
    <script src="script/alerts.js"></script>
</body>
</html>
