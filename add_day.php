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

// ✅ Отримуємо додані модулі користувача
$stmt = $pdo->prepare("
    SELECT m.id, m.title, m.description, m.image, COUNT(w.id) AS words_count
    FROM modules m
    LEFT JOIN module_words w ON m.id = w.module_id
    LEFT JOIN user_modules um ON m.id = um.module_id AND um.user_id = ?
    WHERE um.user_id = ?
    GROUP BY m.id
    ORDER BY um.added_at DESC
");
$stmt->execute([$user_id, $user_id]);
$added_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        <!-- СЕКЦІЯ СТВОРЕННЯ НОВОЇ ТЕМИ -->
        <div class="section">
            <form method="POST" class="form">
                <input type="text" name="title" placeholder="Назва уроку / теми" required>
                <button type="submit" class="btn btn-primary">Створити та розпочати</button>
            </form>
            <div class="section-hint">
                <p>Або <a href="function/modules/modules.php" class="hint-link">переглянути модулі</a> та додати готові пакети слів</p>
            </div>
        </div>

        <!-- СЕКЦІЯ ІСНУЮЧИХ ТЕМ -->
        <div class="section">
            <h2 style="margin-bottom: 10px;" class="section-title">🗓️ Ваші теми (<?= count($days) ?>)</h2>

            <?php if ($days): ?>
                <ul class="theme-list" id="themeList">
                    <?php foreach ($days as $day): ?>
                        <?php
                            // Отримуємо кількість слів у темі
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM words WHERE day_id = ?");
                            $stmt->execute([$day['id']]);
                            $words_count = $stmt->fetch()['count'];

                            // 🕒 Форматуємо дату створення
                            $formatted_date = date("d.m.y, H:i", strtotime($day['created_at']));
                        ?>
                        <li class="theme-item" data-id="<?= $day['id'] ?>" onclick="location.href='add_word.php?day_id=<?= $day['id'] ?>'">
                            <div class="theme-info">
                                <strong><?= htmlspecialchars($day['title']) ?></strong>
                                <small>📅 <?= $formatted_date ?> • 📝 <?= $words_count ?> слів</small>
                            </div>
                            <button class="delete-btn" onclick="event.stopPropagation(); deleteTheme(<?= $day['id'] ?>);" title="Видалити тему">
                                🗑️
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <p>Ще не додано жодної теми.</p>
                    <p style="font-size: 12px; margin-top: 8px; color: #6b7280;">Створіть нову тему або імпортуйте слова з модуля</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Нижнє меню -->
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
        <a href="flashcard/practice.php" class="nav-item">
            <span>✏️</span>
            Практика
        </a>
        <a href="profile/" class="nav-item">
            <span>👤</span>
            Профіль
        </a>
    </nav>

    <!-- МОДАЛЬНЕ ВІКНО ВИДАЛЕННЯ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">⚠️</div>
                <h2>Видалити тему?</h2>
                <p>Усі слова в цій темі буде видалено</p>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">Скасувати</button>
                <button class="modal-btn modal-btn-delete" onclick="confirmDelete()">Видалити</button>
            </div>
        </div>
    </div>

    <script>
        let deleteThemeId = null;

        function deleteTheme(id) {
            deleteThemeId = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteThemeId = null;
        }

        function confirmDelete() {
            if (deleteThemeId === null) return;

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'delete_id=' + deleteThemeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const element = document.querySelector(`[data-id="${deleteThemeId}"]`);
                    element.style.animation = 'slideOut 0.3s ease forwards';
                    setTimeout(() => {
                        element.remove();
                        closeDeleteModal();
                        location.reload();
                    }, 300);
                }
            });
        }

        // Закрити модаль при кліку на фон
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</body>
</html>