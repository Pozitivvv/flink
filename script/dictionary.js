let wordIdToDelete = null;
const modal = document.getElementById("deleteModal");
const cancelBtn = document.getElementById("cancelDelete");
const confirmBtn = document.getElementById("confirmDelete");

// 🔊 Функція озвучування слова
function playWord(word) {
  // Основний метод: Web Speech API (працює офлайн)
  if ("speechSynthesis" in window) {
    // Зупиняємо попереднє озвучування якщо є
    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(word);
    utterance.lang = "de-DE";
    utterance.rate = 0.85; // трохи повільніше для кращого розуміння
    utterance.pitch = 1.0;
    utterance.volume = 1.0;

    // Додаємо невелику затримку для стабільності
    setTimeout(() => {
      window.speechSynthesis.speak(utterance);
    }, 100);
  } else {
    // Fallback: спробуємо ResponsiveVoice API якщо доступний
    if (typeof responsiveVoice !== "undefined") {
      responsiveVoice.speak(word, "Deutsch Female");
    } else {
      alert("Озвучування недоступне в цьому браузері");
    }
  }
}

// Додаємо обробники для кнопок озвучування
function attachSoundEvents() {
  // Клік на кнопку
  document.querySelectorAll(".sound-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const wordCell = this.closest(".word-cell");
      const word = wordCell.dataset.word;

      // Візуальний фідбек
      this.classList.add("playing");
      setTimeout(() => {
        this.classList.remove("playing");
      }, 300);

      playWord(word);
    });
  });

  // Клік на всю комірку зі словом
  document.querySelectorAll(".word-cell").forEach((cell) => {
    cell.style.cursor = "pointer";
    cell.addEventListener("click", function (e) {
      // Якщо клік був безпосередньо на кнопці, вона вже обробила подію
      if (e.target.classList.contains("sound-btn")) return;

      const word = this.dataset.word;
      const soundBtn = this.querySelector(".sound-btn");

      // Візуальний фідбек на кнопку
      if (soundBtn) {
        soundBtn.classList.add("playing");
        setTimeout(() => {
          soundBtn.classList.remove("playing");
        }, 300);
      }

      playWord(word);
    });
  });
}

// 🔍 Фільтрація
function filterWords() {
  const dayId = document.getElementById("daySelect").value;
  const search = document.getElementById("searchInput").value;
  const tbody = document.getElementById("wordsTable");

  tbody.classList.add("loading");
  const xhr = new XMLHttpRequest();
  const params =
    "ajax=1&day_id=" +
    encodeURIComponent(dayId) +
    "&search=" +
    encodeURIComponent(search);

  xhr.open("GET", "?" + params, true);
  xhr.onload = function () {
    if (xhr.status === 200) {
      tbody.innerHTML = xhr.responseText;
      tbody.classList.remove("loading");
      attachDeleteEvents();
      attachSoundEvents(); // Підключаємо озвучування після оновлення
    }
  };
  xhr.send();
}

// 🔄 Очистка фильтра
document.getElementById("clearBtn").addEventListener("click", function () {
  document.getElementById("searchInput").value = "";
  document.getElementById("daySelect").value = "";
  filterWords();
});

// 💬 Поиск при вводе
document.getElementById("searchInput").addEventListener("input", filterWords);
document.getElementById("daySelect").addEventListener("change", filterWords);

// 🗑️ Открыть модальное окно удаления
function attachDeleteEvents() {
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      wordIdToDelete = this.dataset.id;
      modal.classList.add("active");
    });
  });
}

// Закрыть модальное окно
cancelBtn.addEventListener("click", () => {
  modal.classList.remove("active");
  wordIdToDelete = null;
});

// Закрыть при клике вне модального окна
modal.addEventListener("click", (e) => {
  if (e.target === modal) {
    modal.classList.remove("active");
    wordIdToDelete = null;
  }
});

// Подтвердить удаление
confirmBtn.addEventListener("click", function () {
  if (wordIdToDelete) {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    formData.append("delete_id", wordIdToDelete);
    xhr.open("POST", "", true);
    xhr.onload = function () {
      if (xhr.responseText.trim() === "success") {
        modal.classList.remove("active");
        wordIdToDelete = null;
        filterWords();
      }
    };
    xhr.send(formData);
  }
});

// Закриття модалки по ESC
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape" && modal.classList.contains("active")) {
    modal.classList.remove("active");
    wordIdToDelete = null;
  }
});

// Підключаємо події після загрузки
document.addEventListener("DOMContentLoaded", function () {
  attachDeleteEvents();
  attachSoundEvents();
});
