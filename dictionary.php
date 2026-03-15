<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$limit = 30; // Сколько слов грузить за раз

// ✅ Удаление слова
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM words WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    exit('success');
}
// ✅ Редактирование слова
if (isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $article = trim($_POST['article']);
    $german = trim($_POST['german']);
    $translation = trim($_POST['translation']);
    $day_id = !empty($_POST['day_id']) ? (int)$_POST['day_id'] : null;
    $description = trim($_POST['description']);

    $stmt = $pdo->prepare("UPDATE words SET article = ?, german = ?, translation = ?, day_id = ?, description = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$article, $german, $translation, $day_id, $description, $edit_id, $user_id]);
    exit('success');
}

// ✅ Получаем темы (для фильтра)
$stmt = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ AJAX Загрузка слов (Фильтр + Пагинация)
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $selected_day = isset($_GET['day_id']) && $_GET['day_id'] !== '' ? (int)$_GET['day_id'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

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

    $query .= " ORDER BY w.created_at DESC LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($words) {
        foreach ($words as $word) {
            $fullWord = trim(($word['article'] ? $word['article'] . ' ' : '') . $word['german']);
            $hasDesc = !empty($word['description']);

            // Основная строка
            echo '<tr class="word-row" 
                      data-id="' . $word['id'] . '" 
                      data-article="' . htmlspecialchars($word['article'] ?? '') . '" 
                      data-german="' . htmlspecialchars($word['german'] ?? '') . '" 
                      data-translation="' . htmlspecialchars($word['translation'] ?? '') . '" 
                      data-day-id="' . htmlspecialchars($word['day_id'] ?? '') . '" 
                      data-description="' . htmlspecialchars($word['description'] ?? '') . '">';

            echo '<td class="article-cell">' . htmlspecialchars($word['article']) . '</td>';
            echo '<td class="word-cell" data-word="' . htmlspecialchars($fullWord) . '">';
            echo '<span class="word-text"><strong>' . htmlspecialchars($word['german']) . '</strong>' . $hasDescIcon . '</span>';
            echo '</td>';
            echo '<td class="translation-cell">' . htmlspecialchars($word['translation']) . '</td>';
            echo '<td class="day-cell">' . ($word['day_title'] ? htmlspecialchars($word['day_title']) : '—') . '</td>';

            // Кнопки
            echo '<td class="action-cell" style="white-space: nowrap;">';
            echo '<button class="expand-btn" title="Розгорнути">▾</button>';
            echo '<button class="edit-btn" title="Редагувати">✏️</button> ';
            echo '<button class="delete-btn" data-id="' . $word['id'] . '">🗑️</button>';
            echo '</td>';
            echo '</tr>';

            // Строка-аккордеон
            echo '<tr class="accordion-row" data-for="' . $word['id'] . '">';
            echo '<td colspan="5" class="accordion-cell">';
            echo '<div class="accordion-content">';
            echo '<div class="acc-full-word">';
            if ($word['article']) echo '<span class="acc-article">' . htmlspecialchars($word['article']) . '</span> ';
            echo '<span class="acc-german">' . htmlspecialchars($word['german']) . '</span>';
            echo '</div>';
            echo '<div class="acc-translation">' . htmlspecialchars($word['translation']) . '</div>';
            if ($word['day_title']) {
                echo '<div class="acc-meta"><span class="acc-label">Тема:</span> ' . htmlspecialchars($word['day_title']) . '</div>';
            }
            if ($hasDesc) {
                echo '<div class="acc-description"><span class="acc-label">Опис:</span><p>' . nl2br(htmlspecialchars($word['description'])) . '</p></div>';
            }
            echo '</div></td></tr>';
        }
    } else {
        // Если это первая страница и слов нет — показываем сообщение. 
        // Если страница > 1 и слов нет — просто ничего не выводим (конец списка).
        if ($page === 1) {
            echo '<tr class="no-data-row"><td colspan="5" class="no-data">Нічого не знайдено.</td></tr>';
        }
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Мій словник | Німецький словник</title>
    <?php include 'function/tags/icons.html'; ?>
    <?php include 'function/tags/seo.html'; ?>
    <link rel="stylesheet" href="assets/dictionary.css?v=0.0.2">
    <link rel="stylesheet" href="assets/main-style.css">
    <style>
        /* Стиль для лоадера при прокрутке */
        #scroll-sentinel {
            height: 20px;
            margin-top: 10px;
        }

        .loading-spinner {
            text-align: center;
            padding: 10px;
            display: none;
            color: #666;
        }

        .loading-spinner.active {
            display: block;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <a href="dashboard.php" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                    <path d="M15 18l-6-6 6-6" />
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

            <div class="custom-select-wrapper" id="filterDaySelectWrapper">
                <div class="custom-select-trigger">
                    <span>Оберіть тему</span>
                    <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="custom-select-options">
                    <div class="custom-option selected" data-value="">Оберіть тему</div>
                    <?php foreach ($days as $day): ?>
                        <div class="custom-option" data-value="<?= $day['id'] ?>"><?= htmlspecialchars($day['title']) ?></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="day_id" id="daySelect" value="">
            </div>
            <div style="flex: 1;">
                <input type="text" name="search" id="searchInput" placeholder="Пошук: der Hund, Hund, собака..." autocomplete="off">
            </div>
            <button type="button" id="clearBtn">Очистити</button>
        </form>

        <div class="audio-hint">
            <div class="hint-row">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                    <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                </svg>
                Натисніть на слово - прослухати вимову
            </div>
            <div class="hint-row">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9" />
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                </svg>
                Зажміть на рядку - редагувати слово
            </div>
            <div class="hint-row">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
                Натисніть ▾ або на рядок - розгорнути деталі
            </div>
        </div>

        <div class="table-wrapper">
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
                </tbody>
            </table>
            <div class="loading-spinner" id="loadingSpinner">Завантаження...</div>
            <div id="scroll-sentinel"></div>
        </div>

        <a href="add_word.php" class="fab">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
        </a>
    </div>

    <nav class="bottom-nav">
        <a href="dashboard.php" class="nav-item"><span>🏠</span>Головна</a>
        <a href="add_day.php" class="nav-item"><span>📘</span>Теми</a>
        <a href="dictionary.php" class="nav-item active"><span>📚</span>Словник</a>
        <a href="flashcard/practice.php" class="nav-item"><span>✏️</span>Практика</a>
        <a href="profile/" class="nav-item"><span>👤</span>Профиль</a>
    </nav>

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
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Редагувати / Опис</h2>
                <p>Змініть дані або додайте опис</p>
            </div>
            <form id="editForm">
                <input type="hidden" id="editId" name="edit_id">

                <div class="form-group">
                    <input type="text" id="editArticle" name="article" placeholder="Артикль (der, die, das)">
                </div>
                <div class="form-group">
                    <input type="text" id="editGerman" name="german" placeholder="Слово" required>
                </div>
                <div class="form-group">
                    <input type="text" id="editTranslation" name="translation" placeholder="Переклад" required>
                </div>
                <div class="form-group">
                    <select id="editDayId" name="day_id">
                        <option value="">Без теми</option>
                        <?php foreach ($days as $day): ?>
                            <option value="<?= $day['id'] ?>"><?= htmlspecialchars($day['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <textarea id="editDescription" name="description" placeholder="Додатковий опис або приклад використання..."></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" id="cancelEdit">Скасувати</button>
                    <button type="submit" class="modal-btn modal-btn-save">Зберегти</button>
                </div>
            </form>
        </div>
    </div>
    <script src="script/main-script.js"></script>
    <script src="script/dictionary.js?v=1.0.1"></script>
</body>

</html>