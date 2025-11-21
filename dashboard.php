<?php

// dashboard.php
session_start();
require_once 'config.php';
require_once 'function/database/version.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем имя пользователя
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userName = $user['name'] ?? 'Друже';

// Получаем рандомное слово дня из базовых слов
$stmt = $pdo->query("SELECT * FROM base_words ORDER BY RAND() LIMIT 1");
$randomWord = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверяем есть ли это слово в словаре пользователя
$inDictionary = false;
if ($randomWord) {
    $check = $pdo->prepare("SELECT id FROM words WHERE user_id = ? AND german = ?");
    $check->execute([$user_id, $randomWord['german']]);
    $inDictionary = $check->fetch() ? true : false;
}

// Получаем статистику
$stats = $pdo->prepare("SELECT COUNT(*) as total FROM words WHERE user_id = ?");
$stats->execute([$user_id]);
$totalWords = $stats->fetchColumn();

$themes = $pdo->prepare("SELECT COUNT(*) as total FROM days WHERE user_id = ?");
$themes->execute([$user_id]);
$totalThemes = $themes->fetchColumn();

// Получаем рандомное НЕРАЗБЛОКИРОВАННОЕ достижение
$stmt = $pdo->prepare("
    SELECT a.* FROM achievements a
    LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
    WHERE ua.id IS NULL
    ORDER BY RAND()
    LIMIT 1
");
$stmt->execute([$user_id]);
$randomAchievement = $stmt->fetch(PDO::FETCH_ASSOC);

// Функция для получения прогресса достижения
function getAchievementProgress($user_id, $condition_type, $condition_value, $pdo) {
    $current = 0;
    $target = $condition_value;
    $percentage = 0;

    switch ($condition_type) {
        case 'words_added':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM words WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current = (int)$stmt->fetchColumn();
            break;
        case 'days_created':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM days WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current = (int)$stmt->fetchColumn();
            break;
        case 'practice_completed':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM practice_history WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current = (int)$stmt->fetchColumn();
            break;
        case 'consecutive_days':
            $stmt = $pdo->prepare("SELECT current_streak FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current = (int)$stmt->fetchColumn() ?: 0;
            break;
    }

    $current = min($current, $target);
    $percentage = $target > 0 ? round(($current / $target) * 100) : 0;

    return [
        'current' => $current,
        'target' => $target,
        'percentage' => $percentage
    ];
}

// Определяем время суток
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Guten Morgen';
    $icon = '🌅';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'Guten Tag';
    $icon = '☀️';
} elseif ($hour >= 17 && $hour < 22) {
    $greeting = 'Guten Abend';
    $icon = '🌆';
} else {
    $greeting = 'Gute Nacht';
    $icon = '🌙';
}

$daysDE = [
    'Monday' => 'Montag',
    'Tuesday' => 'Dienstag',
    'Wednesday' => 'Mittwoch',
    'Thursday' => 'Donnerstag',
    'Friday' => 'Freitag',
    'Saturday' => 'Samstag',
    'Sunday' => 'Sonntag'
];

$monthsDE = [
    'January' => 'Januar',
    'February' => 'Februar',
    'March' => 'März',
    'April' => 'April',
    'May' => 'Mai',
    'June' => 'Juni',
    'July' => 'Juli',
    'August' => 'August',
    'September' => 'September',
    'October' => 'Oktober',
    'November' => 'November',
    'December' => 'Dezember'
];

$dayName = date('l');
$monthName = date('F');

$dateDE = $daysDE[$dayName] . ', ' . date('d') . ' ' . $monthsDE[$monthName] . ' ' . date('Y');
// Получение версии приложения
$app_version = getAppVersion($pdo);
?>


<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Головна | Німецький словник</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#111C1C">
    <?php include 'function/tags/icons.html'; ?>
    <?php include 'function/tags/seo.html'; ?>

    <!-- Для iOS -->
    <link rel="apple-touch-icon" href="assets/icons/icon-512.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <link rel="stylesheet" href="assets/dashboard.css?v=<?= htmlspecialchars($app_version) ?>">
    <link rel="stylesheet" href="assets/main-style.css?v=<?= htmlspecialchars($app_version) ?>">
</head>
<body>
    <!-- PWA Notification -->
    <div class="pwa-notification" id="pwaNotification">
        <div class="pwa-icon">📱</div>
        <div class="pwa-content">
            <div class="pwa-title">Встановити додаток</div>
            <div class="pwa-text">Швидкий доступ без браузера</div>
        </div>
        <div class="pwa-actions">
            <button class="pwa-btn pwa-btn-install" id="pwaInstallBtn">Встановити</button>
            <button class="pwa-btn pwa-btn-close" id="pwaCloseBtn">✕</button>
        </div>
    </div>
    <div class="container">
        <!-- Привітання -->
        <div class="greeting">
            <div class="greeting-icon"><?= $icon ?></div>
            <div class="greeting-text">
                <h1><?= $greeting ?>, <?= htmlspecialchars($userName) ?>!</h1>
                <p><?= $dateDE ?></p>
            </div>
        </div>

        <!-- Виджеты -->
        <div class="widgets">
            <!-- Слово дня -->
            <?php if ($randomWord): ?>
            <div class="widget word-widget">
                <div class="widget-header">
                    <div class="widget-title">
                        <span class="widget-icon">✨</span>
                        Слово дня
                    </div>
                </div>
                <div class="word-of-day">
                    <?php if ($randomWord['article']): ?>
                        <div class="word-article"><?= htmlspecialchars($randomWord['article']) ?></div>
                    <?php endif; ?>
                    <div class="word-german" onclick="playWord('<?= htmlspecialchars(($randomWord['article'] ? $randomWord['article'] . ' ' : '') . $randomWord['german']) ?>')" data-word="<?= htmlspecialchars(($randomWord['article'] ? $randomWord['article'] . ' ' : '') . $randomWord['german']) ?>">
                        <?= htmlspecialchars($randomWord['german']) ?>
                    </div>
                    <?php if ($randomWord['transcription']): ?>
                        <div class="word-transcription"><?= htmlspecialchars($randomWord['transcription']) ?></div>
                    <?php endif; ?>
                    <div class="word-translation"><?= htmlspecialchars($randomWord['translation']) ?></div>
                    <div class="word-actions">
                        <button class="btn-sound" onclick="playWord('<?= htmlspecialchars(($randomWord['article'] ? $randomWord['article'] . ' ' : '') . $randomWord['german']) ?>')">
                            🔊 Озвучити
                        </button>
                        <button class="btn-favorite <?= $inDictionary ? 'active' : '' ?>" onclick="toggleFavorite(<?= $randomWord['id'] ?>, this)">
                            <?= $inDictionary ? '❤️ У словнику' : '🤍 Додати' ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <a href="function/interactive/" class="widget widget-flashcard">
                <div class="widget-header">
                    <div class="widget-title">
                        <span class="widget-icon">🎴</span>
                        Флешкарти
                    </div>
                </div>
                <div class="flashcard-preview">
                    <p>Переглядай слова і переклад та вчи</p>
                    <div class="flashcard-arrow">→</div>
                </div>
            </a>

            <!-- Статистика -->
            <div class="widget">
                <div class="widget-header">
                    <div class="widget-title">
                        <span class="widget-icon">📊</span>
                        Статистика
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $totalWords ?></div>
                        <div class="stat-label">Слів </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $totalThemes ?></div>
                        <div class="stat-label">Тем створено</div>
                    </div>
                </div>
            </div>

            <!-- Рандомное достижение вместо прогресса -->
            <?php if ($randomAchievement): ?>
            <a href="function/achievements/" class="widget widget-achievement">
                <div class="widget-header">
                    <div class="widget-title">
                        <span class="widget-icon">🎯</span>
                        Виконай досягнення
                    </div>
                </div>
                <div class="achievement-preview">
                    <div class="achievement-icon"><?= htmlspecialchars($randomAchievement['icon']) ?></div>
                    <div class="achievement-info">
                        <div class="achievement-name"><?= htmlspecialchars($randomAchievement['title']) ?></div>
                        <div class="achievement-desc"><?= htmlspecialchars($randomAchievement['description']) ?></div>
                    </div>
                    <div class="achievement-arrow">→</div>
                </div>
            </a>
            <?php endif; ?>
        </div>

        <!-- Швидкі дії -->
        <div class="action-grid">
            <a href="add_word.php" class="action-card">
                <div class="action-icon">✍️</div>
                <div class="action-text">
                    <h3>Додати слово</h3>
                    <p>Швидке додавання</p>
                </div>
            </a>
            <a href="add_day.php" class="action-card">
                <div class="action-icon">📘</div>
                <div class="action-text">
                    <h3>Нова тема</h3>
                    <p>Створити урок</p>
                </div>
            </a>
            <a href="flashcard/practice.php" class="action-card">
                <div class="action-icon">🧠</div>
                <div class="action-text">
                    <h3>Практика</h3>
                    <p>Тестування</p>
                </div>
            </a>
            <a href="dictionary.php" class="action-card">
                <div class="action-icon">📚</div>
                <div class="action-text">
                    <h3>Словник</h3>
                    <p>Всі слова</p>
                </div>
            </a>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="dashboard.php" class="nav-item active">
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

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('✅ Service Worker зарегистрирован:', reg.scope))
            .catch(err => console.log('❌ SW error:', err));
        }
    </script>

    <script src="script/voice.js"></script>
    <script src="script/check-pwa.js"></script>
    <script>
        // Додавання до словника
        function toggleFavorite(wordId, btn) {
            const isActive = btn.classList.contains('active');
            if (isActive) return;

            btn.disabled = true;
            btn.innerHTML = '⏳ Додаємо...';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'function/add_base_word.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = xhr.responseText.trim();

                    if (response === 'success' || response === 'exists') {
                        btn.classList.add('active');
                        btn.innerHTML = '❤️ У словнику';
                        btn.disabled = false;

                        const totalWordsElem = document.querySelector('.stat-card .stat-value');
                        if (totalWordsElem) {
                            let count = parseInt(totalWordsElem.textContent) || 0;
                            totalWordsElem.textContent = count + 1;
                        }
                    } else {
                        btn.innerHTML = '🤍 Додати';
                        btn.disabled = false;
                    }
                } else {
                    btn.innerHTML = '🤍 Додати';
                    btn.disabled = false;
                }
            };
            xhr.onerror = function() {
                btn.innerHTML = '🤍 Додати';
                btn.disabled = false;
            };
            xhr.send('word_id=' + wordId);
        }
    </script>
</body>
</html>