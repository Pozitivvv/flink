<?php
//add_word.php 
session_start();
require_once 'config.php';
require_once 'function/database/version.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$day_id = isset($_GET['day_id']) ? (int)$_GET['day_id'] : null;

// ✅ AJAX - Добавление слова
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add'])) {

    $selected_day = $_POST['day_id'] !== '' ? (int)$_POST['day_id'] : null;

    $article = trim($_POST['article'] ?? '');
    $german = trim($_POST['german'] ?? '');
    $translation = trim($_POST['translation'] ?? '');

    // ✅ Дополнительные поля
    $type = trim($_POST['type'] ?? '');
    if ($type === '') $type = null;

    // ✅ Обробка опису (захист та ліміт)
    $description = trim($_POST['description'] ?? '');
    if ($description !== '') {
        // 1. Вирізаємо всі HTML-теги
        $description = strip_tags($description);

        // 2. Жорстко обрізаємо до 500 символів
        if (mb_strlen($description, 'UTF-8') > 500) {
            $description = mb_substr($description, 0, 500, 'UTF-8');
        }
    } else {
        $description = null;
    }

    // ✅ Коррекция артикля
    if ($article !== '') {
        $article = ucfirst(mb_strtolower($article, 'UTF-8'));
    }

    if ($german !== '' && $translation !== '') {

        // Проверяем уникальность слова
        $check = $pdo->prepare("SELECT id FROM words WHERE user_id = ? AND german = ?");
        $check->execute([$user_id, $german]);

        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => ' Це слово вже є у вашому словнику.']);
        } else {

            // ✅ Вставка слова с новыми полями
            $stmt = $pdo->prepare("
                INSERT INTO words (user_id, day_id, article, german, translation, type, description)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $selected_day, $article, $german, $translation, $type, $description]);

            // 🧩 Обновляем активность и streak
            $stmt = $pdo->prepare("SELECT last_active_date, current_streak FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $streak = 1;
            if ($user) {
                $last = $user['last_active_date'];
                if ($last === $yesterday) $streak = $user['current_streak'] + 1;
                elseif ($last === $today) $streak = $user['current_streak'];

                $stmt = $pdo->prepare("UPDATE users SET last_active_date = ?, current_streak = ? WHERE id = ?");
                $stmt->execute([$today, $streak, $user_id]);
            }

            // 🏆 Ачивки
            require_once 'function/achievements/checkAchievements.php';
            checkAchievements($user_id, 'words_count');
            checkAchievements($user_id, 'perfect_words');
            checkAchievements($user_id, 'morning_activity');
            checkAchievements($user_id, 'night_activity');
            checkAchievements($user_id, 'streak_days');
            checkAchievements($user_id, 'first_login');

            echo json_encode(['status' => 'success', 'message' => ' Слово додано!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Заповніть усі обов\'язкові поля.']);
    }
    exit;
}

// ✅ Удаление слова
if (isset($_POST['delete_id'])) {

    $delete_id = (int)$_POST['delete_id'];

    $check = $pdo->prepare("SELECT id FROM words WHERE id = ? AND user_id = ?");
    $check->execute([$delete_id, $user_id]);

    if ($check->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM words WHERE id = ? AND user_id = ?");
        $stmt->execute([$delete_id, $user_id]);
        echo "success";
    } else {
        echo "error";
    }
    exit;
}

// ✅ Редактирование слова (AJAX)
if (isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $article = trim($_POST['article'] ?? '');
    $german = trim($_POST['german'] ?? '');
    $translation = trim($_POST['translation'] ?? '');
    $type = trim($_POST['type'] ?? '');
    if ($type === '') $type = null;
    $description = trim($_POST['description'] ?? '');

    $stmt = $pdo->prepare("UPDATE words SET article = ?, german = ?, translation = ?, type = ?, description = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$article, $german, $translation, $type, $description, $edit_id, $user_id]);
    exit('success');
}

// ✅ Загружаем темы
$stmt = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Загружаем слова темы
$words = [];
if ($day_id) {
    $stmt = $pdo->prepare("
        SELECT id, german, article, translation, type, description
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

// Визначаємо початковий текст для кастомного селекта тем
$initial_theme_text = "Без теми";
foreach ($days as $day) {
    if ($day_id == $day['id']) {
        $initial_theme_text = htmlspecialchars($day['title']);
        break;
    }
}
$app_version = getAppVersion($pdo);
?>

<!DOCTYPE html>
<html lang="uk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додати слова | Німецький словник</title>
    <?php include 'function/tags/icons.html'; ?>
    <?php include 'function/tags/seo.html'; ?>
    <link rel="stylesheet" href="assets/add-word.css?v=<?= htmlspecialchars($app_version) ?>">
    <link rel="stylesheet" href="assets/main-style.css?v=<?= htmlspecialchars($app_version) ?>">
</head>

<body>
    <div class="container">

        <div class="page-header">
            <a href="#" class="back-btn" onclick="goBack()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </a>
            <h1>✍️ Додати слово</h1>
        </div>

        <div id="message-container"></div>

        <form id="addWordForm">
            <label>Оберіть тему:</label>
            <div class="custom-select-wrapper">
                <div class="custom-select-trigger">
                    <span><?= $initial_theme_text ?></span>
                    <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="custom-select-options">
                    <div class="custom-option <?= empty($day_id) ? 'selected' : '' ?>" data-value="">— Без теми —</div>
                    <?php foreach ($days as $day): ?>
                        <div class="custom-option <?= ($day_id == $day['id']) ? 'selected' : '' ?>" data-value="<?= $day['id'] ?>">
                            <?= htmlspecialchars($day['title']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="day_id" value="<?= htmlspecialchars((string)($day_id ?? '')) ?>">
            </div>

            <input type="text" maxlength="10" name="article" class="smart-input" placeholder="Артикль (der / die / das)">

            <div class="input-wrapper">
                <input type="text" maxlength="50" name="german" class="smart-input" placeholder="Німецьке слово" required>
                <button type="button" id="quickPasteBtn" class="quick-paste-btn" title="Вставити з буфера">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                    </svg>
                </button>
            </div>

            <input type="text" maxlength="100" name="translation" class="smart-input" placeholder="Переклад" required>

            <button type="button" class="advanced-toggle" id="advancedToggleBtn">
                <span>Додати тип та опис (необов'язково)</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>

            <div class="advanced-content" id="advancedContent">
                <div class="advanced-inner">
                    <div style="position: relative; margin-bottom: 12px;">
                        <textarea name="description" id="wordDescription" class="smart-input" maxlength="500" placeholder="Опис, контекст або приклад використання..."></textarea>
                        <span id="charCount" style="position: absolute; bottom: 12px; right: 12px; font-size: 12px; color: #6b6b6b; pointer-events: none;">0 / 500</span>
                    </div>

                    <div class="custom-select-wrapper">
                        <div class="custom-select-trigger">
                            <span>Частина мови (необов'язково)</span>
                            <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div class="custom-select-options">
                            <div class="custom-option selected" data-value="">— Частина мови (необов'язково) —</div>
                            <div class="custom-option" data-value="noun">Іменник</div>
                            <div class="custom-option" data-value="verb">Дієслово</div>
                            <div class="custom-option" data-value="adj">Прикметник</div>
                        </div>
                        <input type="hidden" name="type" value="">
                    </div>
                </div>
            </div>

            <button type="submit">Додати слово</button>
        </form>

        <div style="margin-top: -10px; margin-bottom: 10px;" class="section-hint">
            <p>Або <a href="function/modules/modules.php" class="hint-link">переглянути модулі</a> та додати готові пакети слів</p>
        </div>

        <nav class="bottom-nav">
            <a href="dashboard.php" class="nav-item">
                <span>🏠</span>
                Головна
            </a>
            <a href="add_day.php" class="nav-item ">
                <span>📘</span>
                Теми
            </a>
            <a href="dictionary.php" class="nav-item active">
                <span>📚</span>
                Словник
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

        <?php if ($current_day): ?>
            <h3 style="margin-top:30px;">📘 Слова теми: «<?= htmlspecialchars($current_day) ?>»</h3>
        <?php endif; ?>

        <?php if ($words): ?>
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
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                    Натисніть ▾ - розгорнути деталі
                </div>
            </div>

            <div class="table-wrapper">
                <table id="wordsTable">
                    <thead>
                        <tr>
                            <th>Артикль</th>
                            <th>Слово</th>
                            <th>Переклад</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($words as $word): ?>
                            <?php
                            $fullWord = trim(($word['article'] ? $word['article'] . ' ' : '') . $word['german']);
                            $hasDesc = !empty($word['description']);
                            $hasDescIcon = $hasDesc ? '<span class="has-desc" title="Є опис">📝</span>' : '';
                            ?>
                            <tr class="word-row"
                                data-id="<?= $word['id'] ?>"
                                data-article="<?= htmlspecialchars($word['article'] ?? '') ?>"
                                data-german="<?= htmlspecialchars($word['german'] ?? '') ?>"
                                data-translation="<?= htmlspecialchars($word['translation'] ?? '') ?>"
                                data-type="<?= htmlspecialchars($word['type'] ?? '') ?>"
                                data-description="<?= htmlspecialchars($word['description'] ?? '') ?>">

                                <td class="article-cell"><?= htmlspecialchars($word['article']) ?></td>
                                <td class="word-cell" data-word="<?= htmlspecialchars($fullWord) ?>">
                                    <span class="word-text"><strong><?= htmlspecialchars($word['german']) ?></strong><?= $hasDescIcon ?></span>
                                </td>
                                <td class="translation-cell"><?= htmlspecialchars($word['translation']) ?></td>

                                <td class="action-cell" style="white-space: nowrap;">
                                    <button class="expand-btn" title="Розгорнути">▾</button>
                                    <button class="edit-btn" title="Редагувати">✏️</button>
                                    <button class="delete-btn" data-id="<?= $word['id'] ?>">🗑️</button>
                                </td>
                            </tr>

                            <tr class="accordion-row" data-for="<?= $word['id'] ?>">
                                <td colspan="4" class="accordion-cell">
                                    <div class="accordion-content">
                                        <div class="acc-full-word">
                                            <?php if ($word['article']) echo '<span class="acc-article">' . htmlspecialchars($word['article']) . '</span> '; ?>
                                            <span class="acc-german"><?= htmlspecialchars($word['german']) ?></span>
                                        </div>
                                        <div class="acc-translation"><?= htmlspecialchars($word['translation']) ?></div>
                                        <?php if ($hasDesc): ?>
                                            <div class="acc-description"><span class="acc-label">Опис:</span>
                                                <p><?= nl2br(htmlspecialchars($word['description'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($current_day): ?>
            <div class="table-wrapper">
                <table id="wordsTable">
                    <thead>
                        <tr>
                            <th>Артикль</th>
                            <th>Слово</th>
                            <th>Переклад</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #6b6b6b; padding: 40px 20px;">
                                Поки що немає слів у цій темі.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div class="modal-overlay" id="deleteModal">
            <div class="modal">
                <div class="modal-header">
                    <div class="modal-icon">🗑️</div>
                    <h2>Видалити слово?</h2>
                </div>
                <p style="text-align: center; color: #9ca3af; margin-bottom: 20px;">Ви впевнені, що хочете видалити це слово з вашого словника? Цю дію неможливо скасувати.</p>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" id="cancelDelete">Скасувати</button>
                    <button type="button" class="modal-btn modal-btn-delete" id="confirmDelete">Видалити</button>
                </div>
            </div>
        </div>
        <div class="modal-overlay" id="editModal">
            <div class="modal">
                <div class="modal-header">
                    <h2>Редагувати слово</h2>
                    <p>Змініть дані або додайте опис</p>
                </div>
                <form id="editForm" autocomplete="off">

                    <input type="hidden" id="editId" name="edit_id">

                    <div class="form-group" style="margin-bottom: 12px;">
                        <input type="text" id="editArticle" name="article" class="smart-input" placeholder="Артикль (der, die, das)" autocomplete="nope">
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <input type="text" id="editGerman" name="german" class="smart-input" placeholder="Слово" required autocomplete="nope">
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <input type="text" id="editTranslation" name="translation" class="smart-input" placeholder="Переклад" required autocomplete="nope">
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <div class="custom-select-wrapper" id="editTypeSelectWrapper">
                            <div class="custom-select-trigger">
                                <span>— Частина мови —</span>
                                <svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="custom-select-options">
                                <div class="custom-option" data-value="">— Частина мови —</div>
                                <div class="custom-option" data-value="noun">Іменник</div>
                                <div class="custom-option" data-value="verb">Дієслово</div>
                                <div class="custom-option" data-value="adj">Прикметник</div>
                            </div>
                            <input type="hidden" name="type" id="editType" value="">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <textarea id="editDescription" name="description" class="smart-input" placeholder="Додатковий опис або приклад використання..."></textarea>
                    </div>

                    <div class="modal-buttons">
                        <button type="button" class="modal-btn modal-btn-cancel" id="cancelEdit">Скасувати</button>
                        <button type="submit" class="modal-btn modal-btn-save" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">Зберегти</button>
                    </div>
                </form>
            </div>
        </div>
        <div id="smartPasteOverlay" class="sp-overlay">
            <div class="sp-modal">
                <div class="sp-header">
                    <h3>✨ Розумна вставка</h3>
                    <button type="button" class="sp-close" id="smartPasteClose">×</button>
                </div>

                <div>
                    <p class="sp-text-hint">Ми знайшли переклад у скопійованому тексті. Бажаєте автоматично розподілити його по полях?</p>

                    <div class="sp-content-box">
                        <p id="sp-article-text" style="display:none; color: #3b82f6; font-weight: 600; font-size: 14px; margin-bottom: 4px;"></p>
                        <p id="sp-word-text" style="font-size: 18px; font-weight: bold; color: #fff; margin-bottom: 4px;"></p>
                        <p id="sp-trans-text" style="color: #9ca3af; font-size: 15px;"></p>
                    </div>
                </div>

                <div class="sp-buttons">
                    <button type="button" class="sp-btn sp-btn-cancel" id="smartPasteCancel" style="font-weight: 400;">Звичайне</button>
                    <button type="button" class="sp-btn sp-btn-apply" id="smartPasteApply" style="transition: none; transform: none !important;">Розподілити</button>
                </div>
            </div>
        </div>
        <script src="script/smart-past.js"></script>
        <script src="script/add-word.js"></script>

</body>

</html>