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

    // ✅ Делаем первую букву артикля заглавной
    if ($article !== '') {
        $article = ucfirst(mb_strtolower($article, 'UTF-8'));
    }

    if ($german !== '' && $translation !== '') {
        $check = $pdo->prepare("SELECT id FROM words WHERE user_id = ? AND german = ?");
        $check->execute([$user_id, $german]);
        
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => ' Це слово вже є у вашому словнику.']);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO words (user_id, day_id, article, german, translation)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $selected_day, $article, $german, $translation]);

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

            // 🏆 Перевіряємо досягнення
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


// ✅ AJAX видалення слова
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

// Получаем список всех тем пользователя
$stmt = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем слова выбранной темы
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

        <div id="message-container"></div>

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
            <a href="dashboard.php" class="nav-item">
                <span>🏠</span>
                Головна
            </a>
            <a href="add_day.php" class="nav-item">
                <span>📘</span>
                Теми
            </a>
            <a href="dictionary.php" class="nav-item">
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

    <script>
        let wordIdToDelete = null;
        const modal = document.getElementById("deleteModal");
        const cancelBtn = document.getElementById("cancelDelete");
        const confirmBtn = document.getElementById("confirmDelete");
        const messageContainer = document.getElementById("message-container");

        // AJAX добавление слова
        document.getElementById("addWordForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append("ajax_add", "1");
            
            fetch("", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.message, data.status);
                if (data.status === 'success') {
                    document.getElementById("addWordForm").reset();
                    // Перезагружаем слова если нужно
                    setTimeout(() => location.reload(), 2000);
                }
            });
        });

        // Показ сообщения
        function showMessage(msg, status) {
            const message = document.createElement("div");
            message.className = `message ${status === "success" ? "success" : "error"}`;
            message.textContent = msg;

            messageContainer.innerHTML = "";
            messageContainer.appendChild(message);

            // Плавное появление
            message.style.opacity = "0";
            setTimeout(() => (message.style.opacity = "1"), 50);

            // Авто-скрытие для успешных сообщений
            if (status === "success") {
                setTimeout(() => {
                    message.style.opacity = "0";
                    setTimeout(() => (messageContainer.innerHTML = ""), 300);
                }, 6000);
            }
        }


        // 🔊 Функция озвучивания слова
        function playWord(word) {
            if ("speechSynthesis" in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(word);
                utterance.lang = "de-DE";
                utterance.rate = 0.85;
                utterance.pitch = 1.0;
                utterance.volume = 1.0;
                setTimeout(() => {
                    window.speechSynthesis.speak(utterance);
                }, 100);
            }
        }

        // Добавляем обработчики для озвучивания слов
        function attachSoundEvents() {
            document.querySelectorAll(".word-cell").forEach((cell) => {
                cell.style.cursor = "pointer";
                cell.addEventListener("click", function (e) {
                    e.preventDefault();
                    const word = this.dataset.word;
                    this.style.transform = "scale(1.02)";
                    setTimeout(() => {
                        this.style.transform = "scale(1)";
                    }, 200);
                    playWord(word);
                });
            });
        }

        // Удаление слова
        function attachDeleteEvents() {
            document.querySelectorAll(".delete-btn").forEach((btn) => {
                btn.addEventListener("click", function (e) {
                    e.stopPropagation();
                    wordIdToDelete = this.dataset.id;
                    modal.classList.add("active");
                });
            });
        }

        // Закрыть модальное окно
        if (cancelBtn) {
            cancelBtn.addEventListener("click", () => {
                modal.classList.remove("active");
                wordIdToDelete = null;
            });
        }

        // Закрыть при клике вне модального окна
        if (modal) {
            modal.addEventListener("click", (e) => {
                if (e.target === modal) {
                    modal.classList.remove("active");
                    wordIdToDelete = null;
                }
            });
        }

        // Подтвердить удаление
        if (confirmBtn) {
            confirmBtn.addEventListener("click", function () {
                if (wordIdToDelete) {
                    const xhr = new XMLHttpRequest();
                    const formData = new FormData();
                    formData.append("delete_id", wordIdToDelete);
                    xhr.open("POST", "", true);
                    xhr.onload = function () {
                        if (xhr.responseText.trim() === "success") {
                            const row = document.getElementById("word-" + wordIdToDelete);
                            if (row) {
                                row.style.opacity = "0";
                                row.style.transform = "translateX(-20px)";
                                setTimeout(() => row.remove(), 300);
                            }
                            modal.classList.remove("active");
                            wordIdToDelete = null;
                        }
                    };
                    xhr.send(formData);
                }
            });
        }

        // Закрытие модалки по ESC
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && modal && modal.classList.contains("active")) {
                modal.classList.remove("active");
                wordIdToDelete = null;
            }
        });

        // Возврат назад
        function goBack() {
            window.history.back();
        }

        // Подключаем события после загрузки
        document.addEventListener("DOMContentLoaded", function () {
            attachDeleteEvents();
            attachSoundEvents();
        });
    </script>
</body>
</html>