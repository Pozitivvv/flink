<?php
//add_word.php 
session_start();
require_once 'config.php';

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

    // ✅ Новое поле "частина мови"
    $type = trim($_POST['type'] ?? '');
    if ($type === '') $type = null;

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

            // ✅ Вставка слова с типом
            $stmt = $pdo->prepare("
                INSERT INTO words (user_id, day_id, article, german, translation, type)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $selected_day, $article, $german, $translation, $type]);

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
        echo json_encode(['status' => 'error', 'message' => 'Заповніть усі поля.']);
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

// ✅ Загружаем темы
$stmt = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Загружаем слова темы
$words = [];
if ($day_id) {

    $stmt = $pdo->prepare("
        SELECT id, german, article, translation, type
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

        <div id="message-container"></div>
        
        <!-- ✅ ДОБАВЛЕН ПОЛЕ TYPE -->
        <form id="addWordForm">
            <label for="day_id">Оберіть тему (необов'язково):</label>
            <select name="day_id" id="day_id">
                <option value="">— Без теми —</option>
                <?php foreach ($days as $day): ?>
                    <option value="<?= $day['id'] ?>" <?= ($day_id == $day['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($day['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="article" placeholder="Артикль (der / die / das)">
            <input type="text" name="german" placeholder="Німецьке слово" required>
            <input type="text" name="translation" placeholder="Переклад" required>

            <label for="type">Частина мови:</label>
            <select name="type" id="type">
                <option value="">— Необов'язково —</option>
                <option value="noun">Іменник</option>
                <option value="verb">Дієслово</option>
                <option value="adj">Прикметник</option>
            </select>

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


        <?php if ($current_day): ?>
            <h3 style="margin-top:30px;">📘 Слова теми: «<?= htmlspecialchars($current_day) ?>»</h3>
        <?php endif; ?>


        <?php if ($words): ?>
            <table>
                <tr>
                    <th>Артикль</th>
                    <th>Слово</th>
                    <th>Переклад</th>
                    <th>Тип</th>
                    <th></th>
                </tr>

                <?php foreach ($words as $word): ?>
                    <tr id="word-<?= $word['id'] ?>">
                        <td><?= htmlspecialchars($word['article']) ?></td>
                        <td><b><?= htmlspecialchars($word['german']) ?></b></td>
                        <td><?= htmlspecialchars($word['translation']) ?></td>
                        <td><?= htmlspecialchars($word['type']) ?></td>
                        <td><button class="delete-btn" data-id="<?= $word['id'] ?>">🗑️</button></td>
                    </tr>
                <?php endforeach; ?>                
            </table>
        <?php elseif ($current_day): ?>
            <p style="color:#7f8c8d;margin-top:20px;">Поки що немає слів у цій темі.</p>
        <?php endif; ?>
            
    <script>
        // ✅ AJAX обработка — без изменений
        let wordIdToDelete = null;
        const modal = document.getElementById("deleteModal");
        const cancelBtn = document.getElementById("cancelDelete");
        const confirmBtn = document.getElementById("confirmDelete");
        const messageContainer = document.getElementById("message-container");

        document.getElementById("addWordForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append("ajax_add", "1");
            
            fetch("", {
                method: "POST",
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                showMessage(data.message, data.status);
                if (data.status === 'success') {
                    this.reset();
                    setTimeout(() => location.reload(), 2000);
                }
            });
        });

        function showMessage(msg, status) {
            const message = document.createElement("div");
            message.className = `message ${status}`;
            message.textContent = msg;
            messageContainer.innerHTML = "";
            messageContainer.appendChild(message);
        }

        function goBack() { window.history.back(); }
    </script>

</body>
</html>
