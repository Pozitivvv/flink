console.log("🚀 Dictionary System Loaded (Infinite Scroll)");

// ================= VARIABLES =================
let currentPage = 1;
let isLoading = false;
let hasMoreWords = true; // Есть ли еще слова на сервере
let filterTimeout = null; // Для задержки поиска

const wordsTable = document.getElementById("wordsTable");
const loadingSpinner = document.getElementById("loadingSpinner");
const sentinel = document.getElementById("scroll-sentinel");

// ================= VOICE SYSTEM =================
function playWord(word) {
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
  if (isIOS) {
    playWithWikimedia(word);
  } else {
    fallbackPlayWord(word);
  }
}

function playWithWikimedia(word) {
  const articles = [
    "der",
    "die",
    "das",
    "den",
    "dem",
    "des",
    "einen",
    "einem",
    "eines",
    "einer",
  ];
  let wordWithoutArticle = word;

  for (let article of articles) {
    if (word.toLowerCase().startsWith(article + " ")) {
      wordWithoutArticle = word.substring(article.length + 1);
      break;
    }
  }

  const encodedWord = encodeURIComponent(wordWithoutArticle);
  const audioUrl = `https://commons.wikimedia.org/wiki/Special:FilePath/De-${encodedWord}.ogg`;
  const audio = new Audio(audioUrl);

  const timeout = setTimeout(() => {
    audio.pause();
    fallbackPlayWord(word);
  }, 3000);

  audio.onplay = () => clearTimeout(timeout);
  audio.onerror = () => fallbackPlayWord(word);
  audio.play().catch(() => fallbackPlayWord(word));
}

function fallbackPlayWord(word) {
  if ("speechSynthesis" in window) {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(word);
    utterance.lang = "de-DE";

    const voices = window.speechSynthesis.getVoices();
    const germanVoice = voices.find(
      (v) => v.lang === "de-DE" || v.lang.startsWith("de-")
    );
    if (germanVoice) utterance.voice = germanVoice;

    setTimeout(() => window.speechSynthesis.speak(utterance), 100);
  }
}

// Функция назначения событий на новые элементы
function attachEvents() {
  // Клик по слову для озвучки
  document
    .querySelectorAll(".word-cell:not(.event-attached)")
    .forEach((cell) => {
      cell.classList.add("event-attached");
      cell.style.cursor = "pointer";
      cell.addEventListener("click", function (e) {
        const word = this.dataset.word;
        // Визуальный эффект
        const textSpan = this.querySelector(".word-text");
        if (textSpan) {
          textSpan.style.opacity = "0.5";
          setTimeout(() => (textSpan.style.opacity = "1"), 200);
        }
        playWord(word);
      });
    });

  // Кнопки удаления
  document
    .querySelectorAll(".delete-btn:not(.event-attached)")
    .forEach((btn) => {
      btn.classList.add("event-attached");
      btn.addEventListener("click", function () {
        wordIdToDelete = this.dataset.id;
        const modal = document.getElementById("deleteModal");
        modal.classList.add("active");
      });
    });
}

// ================= LOAD DATA LOGIC =================

// Главная функция загрузки
function loadWords(reset = false) {
  if (isLoading || (!hasMoreWords && !reset)) return;

  isLoading = true;
  loadingSpinner.classList.add("active");

  if (reset) {
    currentPage = 1;
    hasMoreWords = true;
    wordsTable.innerHTML = "";
  }

  const dayId = document.getElementById("daySelect").value;
  const search = document.getElementById("searchInput").value;

  const params = new URLSearchParams({
    ajax: "1",
    page: currentPage,
    day_id: dayId,
    search: search,
  });

  fetch("?" + params.toString())
    .then((response) => response.text())
    .then((html) => {
      loadingSpinner.classList.remove("active");

      if (!html.trim()) {
        hasMoreWords = false;
        if (currentPage === 1) {
          wordsTable.innerHTML =
            '<tr><td colspan="5" class="no-data">Нічого не знайдено.</td></tr>';
        }
        isLoading = false;
        return;
      }

      // Если данных меньше, чем лимит (простая проверка на глаз, или сервер может возвращать JSON)
      // В данном случае просто добавляем HTML
      wordsTable.insertAdjacentHTML("beforeend", html);

      attachEvents(); // Навешиваем слушатели на новые элементы

      currentPage++;
      isLoading = false;

      // Если пришел пустой ответ или очень короткий, считаем что всё
      if (html.length < 50) {
        hasMoreWords = false;
      }
    })
    .catch((err) => {
      console.error("Error loading words:", err);
      loadingSpinner.classList.remove("active");
      isLoading = false;
    });
}

// ================= INFINITE SCROLL OBSERVER =================
const observerOptions = {
  root: null, // viewport
  rootMargin: "100px", // начинать грузить за 100px до конца
  threshold: 0.1,
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting && !isLoading && hasMoreWords) {
      loadWords(false); // Грузим следующую страницу
    }
  });
}, observerOptions);

// Начинаем следить за "сентинелем"
if (sentinel) observer.observe(sentinel);

// ================= FILTERS & SEARCH =================

// Обработка поиска с задержкой (Debounce)
document.getElementById("searchInput").addEventListener("input", function () {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    loadWords(true); // true = сбросить список и загрузить заново
  }, 400);
});

document.getElementById("daySelect").addEventListener("change", function () {
  loadWords(true);
});

document.getElementById("clearBtn").addEventListener("click", function () {
  document.getElementById("searchInput").value = "";
  document.getElementById("daySelect").value = "";
  loadWords(true);
});

// ================= DELETE LOGIC =================
let wordIdToDelete = null;
const deleteModal = document.getElementById("deleteModal");
const cancelBtn = document.getElementById("cancelDelete");
const confirmBtn = document.getElementById("confirmDelete");

cancelBtn.addEventListener("click", () => {
  deleteModal.classList.remove("active");
  wordIdToDelete = null;
});

deleteModal.addEventListener("click", (e) => {
  if (e.target === deleteModal) {
    deleteModal.classList.remove("active");
  }
});

confirmBtn.addEventListener("click", function () {
  if (wordIdToDelete) {
    const formData = new FormData();
    formData.append("delete_id", wordIdToDelete);

    fetch("", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.text())
      .then((res) => {
        if (res.trim() === "success") {
          deleteModal.classList.remove("active");
          // Удаляем строку из DOM без перезагрузки всего списка
          const btn = document.querySelector(
            `.delete-btn[data-id="${wordIdToDelete}"]`
          );
          if (btn) {
            const row = btn.closest("tr");
            row.style.transition = "opacity 0.5s";
            row.style.opacity = "0";
            setTimeout(() => row.remove(), 500);
          }
          wordIdToDelete = null;
        }
      });
  }
});

// ================= INIT =================
// При первой загрузке страницы запускаем загрузку данных
document.addEventListener("DOMContentLoaded", () => {
  loadWords(true);
  // Полифилл голосов
  if ("speechSynthesis" in window) {
    window.speechSynthesis.onvoiceschanged = () =>
      window.speechSynthesis.getVoices();
  }
});
