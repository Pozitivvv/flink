<?php
session_start();
require_once '../../config.php';
if (!isset($_SESSION['user_id'])) { 
    header('Location: index.php'); 
    exit(); 
}
$user_id = $_SESSION['user_id'];

// Получаем слова в зависимости от выбора
$words = [];
if (isset($_GET['day_id']) && !empty($_GET['day_id'])) {
    // Слова из конкретной темы
    $stmt = $pdo->prepare("SELECT id, article, german, translation FROM words WHERE user_id=? AND day_id=? ORDER BY RAND() LIMIT 20");
    $stmt->execute([$user_id, $_GET['day_id']]);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // 20 случайных слов
    $stmt = $pdo->prepare("SELECT id, article, german, translation FROM words WHERE user_id=? ORDER BY RAND() LIMIT 20");
    $stmt->execute([$user_id]);
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Если нет слов - редирект
if (empty($words)) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ua">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#1a1a2e">
<title>Флешкарти - Wortly DE</title>
<link rel="stylesheet" href="style/flashcard.css">
</head>
<body>
<div class="container">
    <!-- Шапка -->
    <div class="header">
        <a href="index.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </a>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="counter">
            <span id="currentCard">1</span>/<span id="totalCards"><?= count($words) ?></span>
        </div>
    </div>
    <div class="card-hint">Клік або тягніть ← → для перевороту - ↑ Наступне слово</div>
    <!-- Контейнер карточки -->
    <div class="card-container" id="cardContainer">
        <div class="swipe-indicator" id="swipeIndicator">↑</div>
        
        <div class="card-wrapper" id="cardWrapper">
            <div class="card card-front">
                <button class="sound-btn" id="frontSoundBtn">🔊</button>
                <div class="card-article" id="frontArticle"></div>
                <div class="card-word" id="frontWord"></div>
                
            </div>
            <div class="card card-back">
                <button class="sound-btn sound-btn-back" id="backSoundBtn">🔊</button>
                <div class="card-article" id="backArticle"></div>
                <div class="card-word" id="backWord"></div>
                <div class="card-translation" id="backTranslation"></div>
            </div>
        </div>
    </div>

    <!-- Навигация -->
    <div class="navigation">
        <button class="nav-btn" id="prevBtn" onclick="prevCard()">
            ← Назад
        </button>
        <button class="nav-btn" id="nextBtn" onclick="nextCard()">
            Далее →
        </button>
    </div>
</div>

<!-- Модальное окно завершения -->
<div class="completion-modal" id="completionModal">
    <div class="modal-content">
        <div class="modal-icon">🎉</div>
        <h2 class="modal-title">Gute Arbeit</h2>
        <p class="modal-text">Sie haben alles gesehen <?= count($words) ?> Karten</p>
        <button class="modal-btn" onclick="window.location='index.php'">
            Повернутися до тем
        </button>
    </div>
</div>
<script src="../../script/voice.js"></script>
<script>
    const words = <?= json_encode($words) ?>;
    let currentIndex = 0;
    let isFlipped = false;
    

    // Элементы
    const cardWrapper = document.getElementById('cardWrapper');
    const cardContainer = document.getElementById('cardContainer');
    const progressFill = document.getElementById('progressFill');
    const currentCardEl = document.getElementById('currentCard');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const completionModal = document.getElementById('completionModal');
    const swipeIndicator = document.getElementById('swipeIndicator');

    // Элементы карточки
    const frontArticle = document.getElementById('frontArticle');
    const frontWord = document.getElementById('frontWord');
    const backArticle = document.getElementById('backArticle');
    const backWord = document.getElementById('backWord');
    const backTranslation = document.getElementById('backTranslation');

    // Переменные для свайпа
    let startX = 0;
    let startY = 0;
    let currentX = 0;
    let currentY = 0;
    let isDragging = false;
    let swipeDirection = null;
    let isAnimating = false;
    let hasMoved = false;

    // Загрузка карточки
    function loadCard() {
        const word = words[currentIndex];
        
        frontArticle.textContent = word.article || '';
        frontWord.textContent = word.german;
        backArticle.textContent = word.article || '';
        backWord.textContent = word.german;
        backTranslation.textContent = word.translation;
        
        // Обновление прогресса
        currentCardEl.textContent = currentIndex + 1;
        const progress = ((currentIndex + 1) / words.length) * 100;
        progressFill.style.width = progress + '%';
        
        // Обновление кнопок
        prevBtn.disabled = currentIndex === 0;
        nextBtn.textContent = currentIndex === words.length - 1 ? 'Завершити ✓' : 'Далее →';
        
        // Сброс переворота
        isFlipped = false;
        cardWrapper.classList.remove('flipped');
        cardWrapper.classList.add('swipe-in');
        setTimeout(() => cardWrapper.classList.remove('swipe-in'), 400);
    }

    // Переворот карточки
    function flipCard() {
        isFlipped = !isFlipped;
        cardWrapper.classList.toggle('flipped');
    }

    // Клик для переворота
    cardContainer.addEventListener('click', (e) => {
        if (!hasMoved && !isDragging && !e.target.classList.contains('sound-btn') && !e.target.closest('.sound-btn')) {
            flipCard();
        }
    });

    // Touch события
    cardContainer.addEventListener('touchstart', handleStart, { passive: false });
    cardContainer.addEventListener('touchmove', handleMove, { passive: false });
    cardContainer.addEventListener('touchend', handleEnd);

    // Mouse события
    cardContainer.addEventListener('mousedown', handleStart);
    cardContainer.addEventListener('mousemove', handleMove);
    cardContainer.addEventListener('mouseup', handleEnd);
    cardContainer.addEventListener('mouseleave', handleEnd);

    function handleStart(e) {

        if (e.target.closest('.sound-btn')) {
            return;
        }
        if (isAnimating) return;
        e.preventDefault();
        isDragging = true;
        hasMoved = false;
        swipeDirection = null;
        const touch = e.touches ? e.touches[0] : e;
        startX = touch.clientX;
        startY = touch.clientY;
        currentX = touch.clientX;
        currentY = touch.clientY;
        cardWrapper.style.transition = 'none';
        cardWrapper.classList.add('swiping');
    }

    function handleMove(e) {
        if (!isDragging) return;
        e.preventDefault();
        
        const touch = e.touches ? e.touches[0] : e;
        currentX = touch.clientX;
        currentY = touch.clientY;
        const deltaX = currentX - startX;
        const deltaY = currentY - startY;
        
        // Определяем направление свайпа при первом движении
        if (!swipeDirection && (Math.abs(deltaX) > 5 || Math.abs(deltaY) > 5)) {
            swipeDirection = Math.abs(deltaY) > Math.abs(deltaX) ? 'vertical' : 'horizontal';
            hasMoved = true;
        }
        
        if (swipeDirection === 'vertical') {
            // Вертикальный свайп - смена карточки
            const progress = deltaY / window.innerHeight;
            const opacity = 1 - Math.abs(progress) * 0.8;
            
            const rotateY = isFlipped ? 180 : 0;
            cardWrapper.style.transform = `translateY(${deltaY}px) rotateY(${rotateY}deg)`;
            cardWrapper.style.opacity = opacity;
            
            // Показываем индикатор
            if (deltaY < -50 && currentIndex < words.length - 1) {
                swipeIndicator.classList.add('visible');
            } else {
                swipeIndicator.classList.remove('visible');
            }
        } else if (swipeDirection === 'horizontal') {
            // Горизонтальный свайп - переворот карточки
            const baseRotation = isFlipped ? 180 : 0;
            const sensitivity = 0.5;
            const rotation = baseRotation + (deltaX * sensitivity);
            cardWrapper.style.transform = `rotateY(${rotation}deg)`;
        }
    }

    function handleEnd(e) {
        if (!isDragging) return;
        isDragging = false;
        
        const deltaX = currentX - startX;
        const deltaY = currentY - startY;
        const horizontalThreshold = 100;
        const verticalThreshold = 80;
        
        swipeIndicator.classList.remove('visible');
        
        cardWrapper.style.transition = 'transform 0.5s ease-out, opacity 0.6s ease';
        cardWrapper.classList.remove('swiping');
        
        if (swipeDirection === 'vertical') {
            // Вертикальный свайп - смена карточки
            if (deltaY < -verticalThreshold && currentIndex < words.length - 1) {
                swipeOutAndNext('up');
            } else {
                const rotateY = isFlipped ? 180 : 0;
                cardWrapper.style.transform = `rotateY(${rotateY}deg)`;
                cardWrapper.style.opacity = '1';
            }
        } else if (swipeDirection === 'horizontal') {
            // Горизонтальный свайп - переворот в зависимости от направления, но всегда переключает сторону
            if (Math.abs(deltaX) > horizontalThreshold) {
                const currentRotation = isFlipped ? 180 : 0;
                const flipDirection = deltaX > 0 ? 1 : -1;
                const targetRotation = currentRotation + (180 * flipDirection);

                cardWrapper.style.transform = `rotateY(${targetRotation}deg)`;

                // После анимации нормализуем rotation к 0 или 180
                setTimeout(() => {
                    isFlipped = !isFlipped;
                    cardWrapper.classList.toggle('flipped');
                    const normalizedRotation = isFlipped ? 180 : 0;
                    cardWrapper.style.transition = 'none';
                    cardWrapper.style.transform = `rotateY(${normalizedRotation}deg)`;
                    setTimeout(() => {
                        cardWrapper.style.transition = 'transform 0.5s ease-out, opacity 0.6s ease';
                    }, 10);
                }, 500); // Соответствует времени transition
            } else {
                // Маленький свайп - вернуться в исходное положение
                const rotateY = isFlipped ? 180 : 0;
                cardWrapper.style.transform = `rotateY(${rotateY}deg)`;
            }
        }
        
        startX = 0;
        currentX = 0;
        startY = 0;
        currentY = 0;
        swipeDirection = null;
    }

    function swipeOutAndNext(direction) {
        isAnimating = true;
        cardWrapper.classList.add(direction === 'up' ? 'swipe-out-up' : 'swipe-out-down');
        
        setTimeout(() => {
            cardWrapper.classList.remove('swipe-out-up', 'swipe-out-down');
            cardWrapper.style.transform = '';
            cardWrapper.style.opacity = '1';
            
            if (direction === 'up') {
                currentIndex++;
            }
            
            loadCard();
            isAnimating = false;
        }, 400);
    }

    // Навигация
    function prevCard() {
        if (currentIndex > 0 && !isAnimating) {
            currentIndex--;
            loadCard();
        }
    }

    function nextCard() {
        if (!isAnimating) {
            if (currentIndex < words.length - 1) {
                swipeOutAndNext('up');
            } else {
                completionModal.classList.add('show');
            }
        }
    }

    // Озвучка
    function playCurrentWord(e) {
        if (e) e.stopPropagation();
        const word = words[currentIndex];
        const fullWord = (word.article ? word.article + ' ' : '') + word.german;
        playWord(fullWord);
    }

    // Обработчики для кнопок звука
    const frontSoundBtn = document.getElementById('frontSoundBtn');
    const backSoundBtn = document.getElementById('backSoundBtn');

    // Click для desktop
    frontSoundBtn.addEventListener('click', playCurrentWord);
    backSoundBtn.addEventListener('click', playCurrentWord);

    // Touchend для мобильных (приоритетнее чем click)
    frontSoundBtn.addEventListener('touchend', (e) => {
        e.preventDefault();
        e.stopPropagation();
        playCurrentWord(e);
    });

    backSoundBtn.addEventListener('touchend', (e) => {
        e.preventDefault();
        e.stopPropagation();
        playCurrentWord(e);
    });
    // Инициализация
    loadCard();
</script>


</body>
</html>