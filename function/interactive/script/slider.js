let currentIndex = 0;
let isFlipped = false;

// Элементы
const cardWrapper = document.getElementById("cardWrapper");
const cardContainer = document.getElementById("cardContainer");
const progressFill = document.getElementById("progressFill");
const currentCardEl = document.getElementById("currentCard");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const completionModal = document.getElementById("completionModal");
const swipeIndicator = document.getElementById("swipeIndicator");

// Элементы карточки
const frontArticle = document.getElementById("frontArticle");
const frontWord = document.getElementById("frontWord");
const backArticle = document.getElementById("backArticle");
const backWord = document.getElementById("backWord");
const backTranslation = document.getElementById("backTranslation");

// Переменные для свайпа
let startX = 0;
let startY = 0;
let currentX = 0;
let currentY = 0;
let isDragging = false;
let swipeDirection = null;
let isAnimating = false;
let hasMoved = false;

// Make the card smaller (adjust maxHeight as needed)
cardWrapper.style.maxHeight = "300px";
cardWrapper.style.overflowY = "auto";

// Загрузка карточки
function loadCard() {
  const word = words[currentIndex];

  frontArticle.textContent = word.article || "";
  frontWord.textContent = word.german;
  backArticle.textContent = word.article || "";
  backWord.textContent = word.german;
  backTranslation.textContent = word.translation;

  // Обновление прогресса
  currentCardEl.textContent = currentIndex + 1;
  const progress = ((currentIndex + 1) / words.length) * 100;
  progressFill.style.width = progress + "%";

  // Обновление кнопок
  prevBtn.disabled = currentIndex === 0;
  nextBtn.textContent =
    currentIndex === words.length - 1 ? "Завершить ✓" : "Далее →";

  // Сброс переворота
  isFlipped = false;
  cardWrapper.classList.remove("flipped");
  cardWrapper.classList.add("swipe-in");
  setTimeout(() => cardWrapper.classList.remove("swipe-in"), 400);
}

// Переворот карточки
function flipCard() {
  isFlipped = !isFlipped;
  cardWrapper.classList.toggle("flipped");
}

// Клик для переворота
cardContainer.addEventListener("click", (e) => {
  if (
    !hasMoved &&
    !isDragging &&
    !e.target.classList.contains("sound-btn") &&
    !e.target.closest(".sound-btn")
  ) {
    flipCard();
  }
});

// Touch события
cardContainer.addEventListener("touchstart", handleStart, { passive: false });
cardContainer.addEventListener("touchmove", handleMove, { passive: false });
cardContainer.addEventListener("touchend", handleEnd);

// Mouse события
cardContainer.addEventListener("mousedown", handleStart);
cardContainer.addEventListener("mousemove", handleMove);
cardContainer.addEventListener("mouseup", handleEnd);
cardContainer.addEventListener("mouseleave", handleEnd);

function handleStart(e) {
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
  cardWrapper.style.transition = "none";
  cardWrapper.classList.add("swiping");
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
    swipeDirection =
      Math.abs(deltaY) > Math.abs(deltaX) ? "vertical" : "horizontal";
    hasMoved = true;
  }

  if (swipeDirection === "vertical") {
    // Вертикальный свайп - смена карточки
    const progress = deltaY / window.innerHeight;
    const opacity = 1 - Math.abs(progress) * 0.8;

    const rotateY = isFlipped ? 180 : 0;
    cardWrapper.style.transform = `translateY(${deltaY}px) rotateY(${rotateY}deg)`;
    cardWrapper.style.opacity = opacity;

    // Показываем индикатор
    if (deltaY < -50 && currentIndex < words.length - 1) {
      swipeIndicator.classList.add("visible");
    } else {
      swipeIndicator.classList.remove("visible");
    }
  } else if (swipeDirection === "horizontal") {
    // Горизонтальный свайп - переворот карточки
    const baseRotation = isFlipped ? 180 : 0;
    const sensitivity = 0.5;
    const rotation = baseRotation + deltaX * sensitivity;
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

  swipeIndicator.classList.remove("visible");

  cardWrapper.style.transition = "transform 0.5s ease-out, opacity 0.6s ease";
  cardWrapper.classList.remove("swiping");

  if (swipeDirection === "vertical") {
    // Вертикальный свайп - смена карточки
    if (deltaY < -verticalThreshold && currentIndex < words.length - 1) {
      swipeOutAndNext("up");
    } else {
      const rotateY = isFlipped ? 180 : 0;
      cardWrapper.style.transform = `rotateY(${rotateY}deg)`;
      cardWrapper.style.opacity = "1";
    }
  } else if (swipeDirection === "horizontal") {
    // Горизонтальный свайп - переворот в зависимости от направления, но всегда переключает сторону
    if (Math.abs(deltaX) > horizontalThreshold) {
      const currentRotation = isFlipped ? 180 : 0;
      const flipDirection = deltaX > 0 ? 1 : -1;
      const targetRotation = currentRotation + 180 * flipDirection;

      cardWrapper.style.transform = `rotateY(${targetRotation}deg)`;

      // После анимации нормализуем rotation к 0 или 180
      setTimeout(() => {
        isFlipped = !isFlipped;
        cardWrapper.classList.toggle("flipped");
        const normalizedRotation = isFlipped ? 180 : 0;
        cardWrapper.style.transition = "none";
        cardWrapper.style.transform = `rotateY(${normalizedRotation}deg)`;
        setTimeout(() => {
          cardWrapper.style.transition =
            "transform 0.5s ease-out, opacity 0.6s ease";
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
  cardWrapper.classList.add(
    direction === "up" ? "swipe-out-up" : "swipe-out-down"
  );

  setTimeout(() => {
    cardWrapper.classList.remove("swipe-out-up", "swipe-out-down");
    cardWrapper.style.transform = "";
    cardWrapper.style.opacity = "1";

    if (direction === "up") {
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
      swipeOutAndNext("up");
    } else {
      completionModal.classList.add("show");
    }
  }
}

// Озвучка
function playCurrentWord(e) {
  if (e) e.stopPropagation();
  const word = words[currentIndex];
  const fullWord = (word.article ? word.article + " " : "") + word.german;
  playWord(fullWord);
}

// Инициализация
loadCard();
