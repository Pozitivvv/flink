// Получение данных из PHP
const allTranslations = window.phpData.allTranslations;
let words = [];
let current = 0;
let score = 0;
let mode = "normal";
let answered = false;

const q = document.getElementById("question");
const opts = document.getElementById("options");
const prog = document.getElementById("progress");

let selectedDay = window.phpData.selectedDayId;
let selectedMode = "normal";

// Инициализация при загрузке страницы
document.addEventListener("DOMContentLoaded", function () {
  initializeDayButtons();
  initializeModeButtons();
});

// alerts
function showMessage(message, type = "info", duration = 15000) {
  const container = document.getElementById("notifications");

  // Создаём сообщение
  const div = document.createElement("div");
  div.className = `message ${
    type === "error" ? "error" : type === "success" ? "success" : ""
  }`;
  div.textContent = message;

  // Добавляем в контейнер
  container.appendChild(div);

  // Автоудаление
  setTimeout(() => {
    div.style.opacity = "0";
    div.style.transition = "opacity 0.4s";
    setTimeout(() => div.remove(), 400);
  }, duration);
}

// Установка активной кнопки дня
function initializeDayButtons() {
  if (selectedDay && selectedDay != 0) {
    document.querySelectorAll(".day-btn").forEach((btn) => {
      if (btn.dataset.id == selectedDay) {
        btn.classList.add("active");
      }
    });
  } else {
    document.querySelector('.day-btn[data-id="0"]').classList.add("active");
    selectedDay = 0;
  }

  document.querySelectorAll(".day-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      selectedDay = btn.dataset.id;
      document
        .querySelectorAll(".day-btn")
        .forEach((b) => b.classList.remove("active", "selected-day"));
      btn.classList.add("active");
    });
  });
}

// Установка активной кнопки режима
function initializeModeButtons() {
  document.querySelectorAll(".mode-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document
        .querySelectorAll(".mode-btn")
        .forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      selectedMode = btn.dataset.mode;
    });
  });
}

// Начало теста
function startTest() {
  mode = selectedMode;

  fetch("flashcards_loader.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body:
      "day_id=" +
      encodeURIComponent(selectedDay) +
      "&mode=" +
      encodeURIComponent(mode),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.length === 0) {
        showMessage("Немає слів для цього тесту", "error");
        return;
      }
      words = data;
      current = 0;
      score = 0;
      answered = false;

      hideElement("menu");
      showElement("quizContainer");
      document.querySelector(".controls").style.display = "block";
      showQuestion();
    });
}

// Сброс теста - возврат в меню
function resetTest() {
  hideElement("quizContainer");
  hideElement("resultsContainer");
  showElement("menu");
}

// Утилиты для показа/скрытия элементов
function hideElement(id) {
  document.getElementById(id).classList.add("hidden");
}

function showElement(id) {
  document.getElementById(id).classList.remove("hidden");
}

// Получение вариантов ответов
function getOptionsForIndex(correctIndex) {
  if (mode === "articles") {
    return ["Der", "Die", "Das", "—"];
  }

  const correct = words[correctIndex].translation;
  const pool = allTranslations.filter((t) => t !== correct);
  shuffle(pool);

  const options = [correct];
  for (let i = 0; i < pool.length && options.length < 4; i++) {
    if (!options.includes(pool[i])) {
      options.push(pool[i]);
    }
  }

  while (options.length < 4) {
    options.push("(нема перекладу)");
  }

  return shuffle(options);
}

// Перемешивание массива (Fisher-Yates)
function shuffle(arr) {
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}

// Запись ошибки
function recordError(wordId) {
  fetch("record_error.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "word_id=" + wordId,
  });
}

// Удаление ошибки
function removeError(wordId) {
  fetch("remove_error.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "word_id=" + wordId,
  });
}

// Показ вопроса
function showQuestion() {
  if (words.length === 0 || current >= words.length) {
    showResults();
    return;
  }

  answered = false;
  const word = words[current];
  const article = word.article ? word.article + " " : "";
  q.textContent = mode === "articles" ? word.german : article + word.german;

  const options = getOptionsForIndex(current);
  opts.innerHTML = "";

  options.forEach((opt) => {
    const div = document.createElement("div");
    div.className = "option";
    div.textContent = opt;
    div.onclick = () => selectOption(div, opt);
    opts.appendChild(div);
  });

  prog.textContent = `Слово ${current + 1} з ${words.length}`;
}

// Выбор варианта ответа
function selectOption(div, opt) {
  if (answered) return;
  answered = true;

  const word = words[current];
  const isCorrect =
    mode === "articles"
      ? opt === (word.article || "—")
      : opt === word.translation;
  const buttons = document.querySelectorAll(".option");
  buttons.forEach((b) => (b.onclick = null));

  if (isCorrect) {
    div.classList.add("correct");
    score++;
    removeError(word.id);

    if (mode === "errors") {
      words.splice(current, 1);
      answered = false;
      showQuestion();
      return;
    }
  } else {
    div.classList.add("wrong");
    buttons.forEach((b) => {
      if (
        (mode === "articles" && b.textContent === (word.article || "—")) ||
        (mode !== "articles" && b.textContent === word.translation)
      ) {
        b.classList.add("correct");
      }
    });
    recordError(word.id);
  }
}

// Следующий вопрос
function nextQuestion() {
  if (!answered) return;
  current++;
  showQuestion();
}

// Показ результатов
function showResults() {
  hideElement("quizContainer");
  showElement("resultsContainer");

  const percentage = Math.round((score / words.length) * 100);

  // Установка текста результатов
  document.getElementById(
    "finalScore"
  ).textContent = `${score} з ${words.length}`;
  document.getElementById(
    "percentageText"
  ).textContent = `${percentage}% правильних відповідей`;

  // Изменение иконки в зависимости от результата
  const icon = document.querySelector(".results-icon");
  if (percentage === 100) {
    icon.textContent = "🏆";
  } else if (percentage >= 80) {
    icon.textContent = "🎉";
  } else if (percentage >= 60) {
    icon.textContent = "👍";
  } else {
    icon.textContent = "💪";
  }
}
