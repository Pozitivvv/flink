<?php
session_start();
require_once '../../config.php';
require_once 'checkAchievements.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Проверяем все типы достижений перед отображением
$stmt = $pdo->query("SELECT DISTINCT condition_type FROM achievements");
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($types as $type) {
    checkAchievements($user_id, $type);
}

$stmt = $pdo->query("SELECT * FROM achievements ORDER BY id ASC");
$all_achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT achievement_id FROM user_achievements WHERE user_id = ?
");
$stmt->execute([$user_id]);
$user_achievements = $stmt->fetchAll(PDO::FETCH_COLUMN);

function isUnlocked($id, $user_achievements) {
    return in_array($id, $user_achievements);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="achievements.css">
    <link rel="stylesheet" href="../../assets/main-style.css">
    <title>Досягнення</title>

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
            <h1>🏆 Досягнення</h1>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <span class="number"><?php echo count($user_achievements); ?></span>
                <div class="label">Розблоковано</div>
            </div>
            <div class="stat-card">
                <span class="number"><?php echo count($all_achievements); ?></span>
                <div class="label">Всього</div>
            </div>
            <div class="stat-card">
                <span class="number"><?php $total = count($all_achievements); $unlocked = count($user_achievements); echo $total > 0 ? round(($unlocked / $total) * 100) : 0; ?>%</span>
                <div class="label">Прогрес</div>
            </div>
        </div>
        
        <div class="achievements-grid">
            <?php foreach ($all_achievements as $ach): 
                $unlocked = isUnlocked($ach['id'], $user_achievements);
                $progress = getAchievementProgress($user_id, $ach['condition_type'], $ach['condition_value']);
            ?>
                <div class="achievement <?= !$unlocked ? 'locked' : '' ?>">
                    <div class="icon"><?= htmlspecialchars($ach['icon']) ?></div>
                    <div class="title"><?= htmlspecialchars($ach['title']) ?></div>
                    <div class="description"><?= htmlspecialchars($ach['description']) ?></div>
                    <?php if (!in_array($ach['condition_type'], ['night_activity', 'morning_activity'])): ?>
                        <div class="progress-text">
                            <?= $progress['current'] ?> / <?= $progress['target'] ?>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $progress['percentage'] ?>%"></div>
                        </div>
                    <?php endif; ?>
                    <div class="badge <?= $unlocked ? 'unlocked' : 'locked' ?>">
                        <?= $unlocked ? '✅ Отримано' : '🔒 Заблоковано' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <nav class="bottom-nav">
            <a href="../../dashboard.php" class="nav-item">
                <span>🏠</span>
                Головна
            </a>
            <a href="../../add_day.php" class="nav-item">
                <span>📘</span>
                Теми
            </a>
            <a href="../../dictionary.php" class="nav-item">
                <span>📚</span>
                Словник
            </a>
            <a href="../../flashcard/practice.php" class="nav-item">
                <span>✏️</span>
                Практика
            </a>
            <a href="../../profile/" class="nav-item active">
                <span>👤</span>
                Профиль
            </a>
    </nav>
    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>