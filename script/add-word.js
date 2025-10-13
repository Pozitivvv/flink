let wordIdToDelete = null;
const modal = document.getElementById("deleteModal");
const cancelBtn = document.getElementById("cancelDelete");
const confirmBtn = document.getElementById("confirmDelete");

// 🔊 Функція озвучування слова
function playWord(word) {
  if ("speechSynthesis" in window) {
    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(word);
    utterance.lang = "de-DE";
    utterance.rate = 0.85;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;

    setTimeout(() => {
      window.speechSynthesis.speak(utterance);
    }, 100);
  } else {
    console.log("Озвучування недоступне в цьому браузері");
  }
}

// Додаємо обробники для озвучування слів
function attachSoundEvents() {
  document.querySelectorAll(".word-cell").forEach((cell) => {
    cell.style.cursor = "pointer";

    cell.addEventListener("click", function (e) {
      e.preventDefault();
      const word = this.dataset.word;

      // Візуальний фідбек
      this.style.transform = "scale(1.02)";
      setTimeout(() => {
        this.style.transform = "scale(1)";
      }, 200);

      playWord(word);
    });
  });
}

// 🗑️ Открыть модальное окно удаления
function attachDeleteEvents() {
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation(); // Запобігаємо спрацюванню озвучування
      wordIdToDelete = this.dataset.id;
      modal.classList.add("active");
    });
  });
}

// Закрыть модальное окно
if (cancelBtn) {
  cancelBtn.addEventListener("click", () => {
    modal.classList.remove("active");
    wordIdToDelete = null;
  });
}

// Закрыть при клике вне модального окна
if (modal) {
  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.classList.remove("active");
      wordIdToDelete = null;
    }
  });
}

// Подтвердить удаление
if (confirmBtn) {
  confirmBtn.addEventListener("click", function () {
    if (wordIdToDelete) {
      const xhr = new XMLHttpRequest();
      const formData = new FormData();
      formData.append("delete_id", wordIdToDelete);
      xhr.open("POST", "", true);
      xhr.onload = function () {
        if (xhr.responseText.trim() === "success") {
          // Удаляем строку из таблицы
          const row = document.getElementById("word-" + wordIdToDelete);
          if (row) {
            row.style.opacity = "0";
            row.style.transform = "translateX(-20px)";
            setTimeout(() => row.remove(), 300);
          }
          modal.classList.remove("active");
          wordIdToDelete = null;
        }
      };
      xhr.send(formData);
    }
  });
}

// Закриття модалки по ESC
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape" && modal && modal.classList.contains("active")) {
    modal.classList.remove("active");
    wordIdToDelete = null;
  }
});

// Функція повернення назад
function goBack() {
  window.history.back();
}

// Підключаємо події після загрузки
document.addEventListener("DOMContentLoaded", function () {
  attachDeleteEvents();
  attachSoundEvents();
});
