<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }
$user_id = $_SESSION['user_id'];

// Получаем все темы
$stmt = $pdo->prepare("SELECT id, title, created_at FROM days WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Вибір теми</title>
<link rel="stylesheet" href="style/select-day.css">
<link rel="stylesheet" href="../assets/main-style.css">
</head>
<body>
<div class="container">
    <!-- Шапка страницы -->
    <div class="page-header">
        <a href="practice.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </a>
        <h1>🔍 Виберіть тему</h1>
    </div>

    <!-- Поиск -->
    <div class="search-container">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
        </svg>
        <input type="text" id="search" placeholder="Пошук тем..." autocomplete="off">
        <button class="clear-btn" id="clearBtn" style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- Список тем -->
    <div id="dayList" class="day-list">
        <?php if(empty($days)): ?>
            <div class="empty-state">
                <div class="empty-icon">📚</div>
                <h2 class="empty-title">Ще немає тем</h2>
                <p class="empty-text">Створіть свою першу тему для вивчення слів</p>
                <a href="add_day.php" class="empty-btn">
                    Створити тему
                </a>
            </div>
        <?php else: ?>
            <?php foreach($days as $day): ?>
                <div class="day-item" data-title="<?= htmlspecialchars($day['title']) ?>" data-id="<?= $day['id'] ?>">
                    <button class="day-btn" onclick="selectDay('<?= $day['id'] ?>')">
                        <div class="day-info">
                            <span class="day-title"><?= htmlspecialchars($day['title']) ?></span>
                            <span class="day-date">📅 <?= date('d.m.Y', strtotime($day['created_at'])) ?></span>
                        </div>
                        <svg class="day-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Сообщение "Ничего не найдено" -->
    <div id="noResults" class="no-results" style="display: none;">
        <div class="no-results-icon">🔍</div>
        <h3 class="no-results-title">Нічого не знайдено</h3>
        <p class="no-results-text">Спробуйте інший запит</p>
    </div>

    <!-- Нижнее меню -->
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
                Словник
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
</div>

<script>
const search = document.getElementById('search');
const clearBtn = document.getElementById('clearBtn');
const dayList = document.getElementById('dayList');
const noResults = document.getElementById('noResults');
const dayItems = document.querySelectorAll('.day-item');

// Показ/скрытие кнопки очистки
search.addEventListener('input', ()=>{
    const filter = search.value.toLowerCase().trim();
    clearBtn.style.display = filter ? 'flex' : 'none';
    
    let hasVisibleItems = false;
    
    dayItems.forEach(item=>{
        const title = item.dataset.title.toLowerCase();
        const isVisible = title.includes(filter);
        item.style.display = isVisible ? '' : 'none';
        if(isVisible) hasVisibleItems = true;
    });
    
    // Показываем "Ничего не найдено" только если есть темы и ничего не найдено
    if(dayItems.length > 0) {
        noResults.style.display = hasVisibleItems ? 'none' : 'block';
        dayList.style.display = hasVisibleItems ? 'grid' : 'none';
    }
});

// Очистка поиска
clearBtn.addEventListener('click', ()=>{
    search.value = '';
    clearBtn.style.display = 'none';
    dayItems.forEach(item=> item.style.display = '');
    noResults.style.display = 'none';
    dayList.style.display = 'grid';
    search.focus();
});

function selectDay(id){
    // Возврат на practice.php с выбранной темой
    window.location = 'practice.php?day_id=' + id;
}
</script>
</body>
</html>