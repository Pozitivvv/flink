<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Удаление слова
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM words WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    exit('success');
}

// ✅ Получаем темы
$stmt = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ AJAX фильтр
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $selected_day = isset($_GET['day_id']) && $_GET['day_id'] !== '' ? (int)$_GET['day_id'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $query = "SELECT w.*, d.title AS day_title 
              FROM words w 
              LEFT JOIN days d ON w.day_id = d.id 
              WHERE w.user_id = ?";
    $params = [$user_id];

    if ($selected_day) {
        $query .= " AND w.day_id = ?";
        $params[] = $selected_day;
    }

    if ($search !== '') {
        $query .= " AND (
                        w.article LIKE ? OR 
                        w.german LIKE ? OR 
                        w.translation LIKE ? OR
                        CONCAT(w.article, ' ', w.german) LIKE ? OR
                        CONCAT_WS(' ', w.article, w.german) LIKE ?
                    )";
        $params = array_merge($params, array_fill(0, 5, "%$search%"));
    }

    $query .= " ORDER BY w.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($words) {
        foreach ($words as $word) {
            $fullWord = trim(($word['article'] ? $word['article'] . ' ' : '') . $word['german']);
            echo '<tr>';
            echo '<td>' . htmlspecialchars($word['article']) . '</td>';
            echo '<td class="word-cell" data-word="' . htmlspecialchars($fullWord) . '">';
            echo '<span class="word-text"><strong>' . htmlspecialchars($word['german']) . '</strong></span>';
            echo '</td>';
            echo '<td>' . htmlspecialchars($word['translation']) . '</td>';
            echo '<td>' . ($word['day_title'] ? htmlspecialchars($word['day_title']) : '—') . '</td>';
            echo '<td class="delete-cell"><button class="delete-btn" data-id="' . $word['id'] . '">🗑️</button></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="no-data">Нічого не знайдено.</td></tr>';
    }
    exit();
}

// ✅ Первичная загрузка слов
$stmt = $pdo->prepare("SELECT w.*, d.title AS day_title 
                       FROM words w 
                       LEFT JOIN days d ON w.day_id = d.id 
                       WHERE w.user_id = ? 
                       ORDER BY w.created_at DESC");
$stmt->execute([$user_id]);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Мій словник | Німецький словник</title>
    <?php include 'function/tags/icons.html'; ?>
    <?php include 'function/tags/seo.html'; ?>
    <link rel="stylesheet" href="assets/dictionary.css?v=0.0.1">
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
        <h1>📚 Мій словник</h1>
        <a href="add_word.php" class="add-word-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Додати
    </a>
    </div>

    <form class="filter" id="filterForm">
        <select name="day_id" id="daySelect">
            <option value="">Оберіть тему</option>
            <?php foreach ($days as $day): ?>
                <option value="<?= $day['id'] ?>"><?= htmlspecialchars($day['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <div style="flex: 1;">
            <input type="text" name="search" id="searchInput" placeholder="Пошук: der Hund, Hund, собака..." autocomplete="off">
            <div class="search-hint">💡 Можна шукати: "der", "Hund", "der Hund", "собака"</div>
        </div>
        <button type="button" id="clearBtn">🔄 Очистити</button>
    </form>

    <div class="audio-hint">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
        </svg>
        Натисніть на слово, щоб прослухати вимову
    </div>

    <table>
        <thead>
            <tr>
                <th>Артикль</th>
                <th>Слово</th>
                <th>Переклад</th>
                <th>Тема</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="wordsTable">
            <?php if ($words): ?>
                <?php foreach ($words as $word): 
                    $fullWord = trim(($word['article'] ? $word['article'] . ' ' : '') . $word['german']);
                ?>
                    <tr>
                        <td><?= htmlspecialchars($word['article']) ?></td>
                        <td class="word-cell" data-word="<?= htmlspecialchars($fullWord) ?>">
                            <span class="word-text"><strong><?= htmlspecialchars($word['german']) ?></strong></span>
                        </td>
                        <td><?= htmlspecialchars($word['translation']) ?></td>
                        <td><?= $word['day_title'] ? htmlspecialchars($word['day_title']) : '—' ?></td>
                        <td class="delete-cell"><button class="delete-btn" data-id="<?= $word['id'] ?>">🗑️</button></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="no-data">Поки що немає слів.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="add_word.php" class="fab">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
</a>
</div>

<nav class="bottom-nav">
            <a href="dashboard.php" class="nav-item ">
                <span>🏠</span>
                Головна
            </a>
            <a href="add_day.php" class="nav-item ">
                <span>📘</span>
                Теми
            </a>
            <a href="dictionary.php" class="nav-item active">
                <span>📚</span>
                Словарь
            </a>
            <a href="flashcard/practice.php" class="nav-item">
                <span>✏️</span>
                Практика
            </a>
            <a href="profile/" class="nav-item">
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


<script src="script/dictionary.js"></script>
</body>
</html>