<?php
session_start();
require_once '../../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }
$user_id = $_SESSION['user_id'];

// Получаем все темы
$stmt = $pdo->prepare("SELECT id, title, created_at FROM days WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$days = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем общее количество слов
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM words WHERE user_id=?");
$stmt->execute([$user_id]);
$totalWords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Вибір теми</title>
<link rel="stylesheet" href="select-day.css">
<link rel="stylesheet" href="../../assets/main-style.css">
<style>
.random-card {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(59, 130, 246, 0.15));
    border: 2px solid rgba(139, 92, 246, 0.3);
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    animation: fadeIn 0.6s ease;
}

.random-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
}

.random-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.random-icon {
    font-size: 32px;
}

.random-title {
    font-size: 20px;
    font-weight: 600;
    color: #fff;
}

.random-description {
    font-size: 14px;
    color: #9ca3af;
    margin-bottom: 16px;
}

.random-btn {
    background: linear-gradient(135deg, #8b5cf6, #3b82f6);
    border: none;
    color: #fff;
    padding: 14px 24px;
    border-radius: 12px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.random-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
}

.random-btn:active {
    transform: translateY(0);
}

.random-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    margin: 20px 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.empty-state {
    border: none;
    margin-top: 0;
    background: none;
}
</style>
</head>
<body>
<div class="container">
    <!-- Шапка страницы -->
    <div class="page-header">
        <a href="../../dashboard.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </a>
        <h1>🔍 Виберіть тему</h1>
    </div>

    <!-- Карточка случайных слов -->
    <?php if($totalWords > 0): ?>
    <div class="random-card">
        <div class="random-header">
            <span class="random-icon">🎲</span>
            <span class="random-title">Випадкові слова</span>
        </div>
        <p class="random-description">
            Практикуйте 20 випадкових слів з усіх тем для кращого запам'ятовування
        </p>
        <button class="random-btn" onclick="startRandomPractice()">
            <span>🚀</span> Почати практику
        </button>
    </div>
    <?php endif; ?>

    <!-- Заголовок секции тем -->
    <?php if(!empty($days)): ?>
    <div class="section-title">
        📚 Або виберіть тему:
    </div>
    <?php endif; ?>

    <!-- Поиск -->
    <?php if(!empty($days)): ?>
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
    <?php endif; ?>

    <!-- Список тем -->
    <div id="dayList" class="day-list">
        <?php if(empty($days)): ?>
            <div class="empty-state">
                <div class="empty-icon">📚</div>
                <h2 class="empty-title">Ще немає тем</h2>
                <p class="empty-text">Створіть свою першу тему для вивчення слів</p>
                <a href="../../add_day.php" class="empty-btn">
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
        <a href="../../dashboard.php" class="nav-item">
            <span>🏠</span>
            Головна
        </a>
        <a href="../../add_day.php" class="nav-item ">
            <span>📘</span>
            Теми
        </a>
        <a href="../../dictionary.php" class="nav-item">
                <span>📚</span>
                Словарь
            </a>
        <a href="../../flashcard/practice.php" class="nav-item">
            <span>✏️</span>
            Практика
        </a>
        <a href="../../profile/" class="nav-item">
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
if(search) {
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
}

// Переход к случайным словам
function startRandomPractice(){
    window.location = 'flashcards.php';
}

// Переход к теме
function selectDay(id){
    window.location = 'flashcards.php?day_id=' + id;
}
</script>