<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
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

$dayName = date('l'); // день недели на английском
$monthName = date('F'); // месяц на английском

$dateDE = $daysDE[$dayName] . ', ' . date('d') . ' ' . $monthsDE[$monthName] . ' ' . date('Y');

?>


<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Головна | Німецький словник</title>
    <link rel="stylesheet" href="assets/dashboard.css">
    <link rel="stylesheet" href="assets/main-style.css">
</head>
<body>
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

            <!-- Прогресс -->
            <div class="widget">
                <div class="widget-header">
                    <div class="widget-title">
                        <span class="widget-icon">🎯</span>
                        Твій прогрес
                    </div>
                </div>
                <div class="stat-value" style="font-size: 24px; margin-bottom: 8px;">
                    <?= min(100, round(($totalWords / 100) * 100)) ?>%
                </div>
                <div class="stat-label" style="margin-bottom: 12px;">Ціль: 100 слів</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min(100, ($totalWords / 100) * 100) ?>%"></div>
                </div>
            </div>
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
            <a href="flashcard/flashcards.php" class="action-card">
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
        <a href="flashcard/flashcards.php" class="nav-item">
            <span>✏️</span>
            Практика
        </a>
        <a href="settings.php" class="nav-item">
            <span>👤</span>
            Профиль
        </a>
    </nav>

    <script>
        // Озвучування
        function playWord(word) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(word);
                utterance.lang = 'de-DE';
                utterance.rate = 0.85;
                setTimeout(() => window.speechSynthesis.speak(utterance), 100);
            }
        }

        // Додавання до словника
        function toggleFavorite(wordId, btn) {
            const isActive = btn.classList.contains('active');
            if (isActive) return; // уже добавлено

            // Показать процесс
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

                        // Обновляем статистику на странице без перезагрузки
                        const totalWordsElem = document.querySelector('.stat-card .stat-value');
                        if (totalWordsElem) {
                            let count = parseInt(totalWordsElem.textContent) || 0;
                            totalWordsElem.textContent = count + 1;
                        }

                        // Можно обновить прогресс
                        const progressFill = document.querySelector('.progress-fill');
                        if (progressFill) {
                            let total = parseInt(totalWordsElem.textContent) || 0;
                            let percent = Math.min(100, (total / 100) * 100);
                            progressFill.style.width = percent + '%';
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