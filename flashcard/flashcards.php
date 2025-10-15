<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Проверка выбранной темы из select_day.php
$selectedDayId = isset($_GET['day_id']) ? (int)$_GET['day_id'] : 0;
$selectedDayTitle = '';

// Последние 3 темы ИЛИ выбранная тема + последние 2
if ($selectedDayId) {
    $stmt = $pdo->prepare("SELECT id, title FROM days WHERE id=? AND user_id=?");
    $stmt->execute([$selectedDayId, $user_id]);
    $selectedDay = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedDay) {
        $selectedDayTitle = $selectedDay['title'];
        $stmt2 = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? AND id != ? ORDER BY created_at DESC LIMIT 2");
        $stmt2->execute([$user_id, $selectedDayId]);
        $otherDays = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $recentDays = array_merge([$selectedDay], $otherDays);
    } else {
        $stmt3 = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
        $stmt3->execute([$user_id]);
        $recentDays = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        $selectedDayId = 0;
    }
} else {
    $stmt = $pdo->prepare("SELECT id, title FROM days WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$user_id]);
    $recentDays = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt_trans = $pdo->prepare("SELECT translation FROM words WHERE user_id = ?");
$stmt_trans->execute([$user_id]);
$allTranslations = $stmt_trans->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
    <html lang="uk">
    <head>
    <meta charset="UTF-8">
    <title>🧠 Тест на слова</title>
    <link rel="stylesheet" href="style/flashcard.css">
    <link rel="stylesheet" href="../assets/main-style.css">
</head>
<body>
<div class="container">

    <div class="page-header">
            <a href="../dashboard.php" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </a>
            <h1>🧩 Тест на знання слів</h1>
        </div>
    

    <!-- Меню выбора тем и режима -->
<div id="menu">
    
    <div id="recentDays">
        <div id="notifications"></div>
        <strong class="section-title">Останні теми:</strong>
        <button class="day-btn random-btn" data-id="0">🎲 Випадкові 20 слів</button>
        <?php foreach($recentDays as $index => $day): ?>
            <button class="day-btn <?= ($index === 0 && $selectedDayId === $day['id']) ? 'selected-day' : '' ?>" 
                    data-id="<?= $day['id'] ?>"><?= htmlspecialchars($day['title']) ?></button>
        <?php endforeach; ?>
        <button class="all-themes-btn" onclick="window.location='select_day.php'">🔍 Усі теми</button>
    </div>

    <div>
        <strong class="section-title">Режим:</strong>
        <div id="modeButtons">
            <button class="mode-btn active" data-mode="normal">🌍 Звичайний</button>
            <button class="mode-btn" data-mode="errors">❌ Повтор помилок</button>
            <button class="mode-btn" data-mode="articles">🧱 Артиклі</button>
        </div>
    </div>

    <button class="start-btn" onclick="startTest()">Почати тест</button>
</div>

<!-- Контейнер теста -->
<div id="quizContainer" class="hidden">
    <div class="question" id="question"></div>
    <div class="options" id="options"></div>
    <div class="controls">
        <button class="next-btn" onclick="nextQuestion()">Далі</button>
    </div>
    <p id="progress"></p>
</div>

<!-- Результаты теста -->
<div id="resultsContainer" class="hidden">
    <div class="results-card">
        <div class="results-icon">🎉</div>
        <h2 class="results-title">Тест завершено!</h2>
        <div class="results-score">
            <span id="finalScore"></span>
        </div>
        <div class="results-percentage" id="percentageText"></div>
        <button class="results-btn" onclick="resetTest()">
            🔄 Повернутись до тестів
        </button>
    </div>
</div>

    <nav class="bottom-nav">
        <a href="../dashboard.php" class="nav-item">
            <span>🏠</span>
            Головна
        </a>
        <a href="../add_day.php" class="nav-item ">
            <span>📘</span>
            Теми
        </a>
        <a href="../dictionary.php" class="nav-item">
                <span>📚</span>
                Словарь
            </a>
        <a href="#" class="nav-item active">
            <span>✏️</span>
            Практика
        </a>
        <a href="../profile/" class="nav-item">
            <span>👤</span>
            Профиль
        </a>
    </nav>

<script>
// Передача данных из PHP в JavaScript
window.phpData = {
    allTranslations: <?= json_encode(array_values($allTranslations), JSON_UNESCAPED_UNICODE) ?>,
    selectedDayId: <?= $selectedDayId ? $selectedDayId : '0' ?>
};
</script>
<script src="script/flashcard.js"></script>

</body>
</html>