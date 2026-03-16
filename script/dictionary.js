console.log("🚀 Dictionary System Loaded (Infinite Scroll)");

// ================= VARIABLES =================
let currentPage = 1;
let isLoading = false;
let hasMoreWords = true; // Есть ли еще слова на сервере
let filterTimeout = null; // Для задержки поиска

const wordsTable = document.getElementById("wordsTable");
const loadingSpinner = document.getElementById("loadingSpinner");
const sentinel = document.getElementById("scroll-sentinel");

// Функция назначения событий на новые элементы
// Глобальная переменная для предотвращения аудио при долгом нажатии
let preventAudioPlay = false;

function attachEvents() {
  // Клик по слову для озвучки
  document
    .querySelectorAll(".word-cell:not(.event-attached)")
    .forEach((cell) => {
      cell.classList.add("event-attached");
      cell.style.cursor = "pointer";
      cell.addEventListener("click", function (e) {
        if (preventAudioPlay) {
          preventAudioPlay = false; // Сбрасываем флаг, если это был лонг-пресс
          return;
        }
        const word = this.dataset.word;
        const textSpan = this.querySelector(".word-text");
        if (textSpan) {
          textSpan.style.opacity = "0.5";
          setTimeout(() => (textSpan.style.opacity = "1"), 200);
        }

        // ВЫЗЫВАЕМ ФУНКЦИЮ ИЗ ВАШЕГО ОТДЕЛЬНОГО ФАЙЛА voice.js
        if (typeof playWord === "function") {
          playWord(word);
        } else {
          console.error(
            "Функция playWord не найдена. Проверьте подключение voice.js",
          );
        }
      });
    });

  // Кнопки удаления
  document
    .querySelectorAll(".delete-btn:not(.event-attached)")
    .forEach((btn) => {
      btn.classList.add("event-attached");
      btn.addEventListener("click", function () {
        wordIdToDelete = this.dataset.id;
        document.getElementById("deleteModal").classList.add("active");
      });
    });

  // Логика редактирования (Кнопка + Long Press на телефоне)
  document.querySelectorAll(".word-row:not(.edit-attached)").forEach((row) => {
    row.classList.add("edit-attached");

    let pressTimer;

    // Функция открытия модалки
    const triggerEdit = () => {
      preventAudioPlay = true; // Отключаем звук для этого клика
      openEditModal(row);
    };

    // Слушатель для мобильных (Long Press)
    row.addEventListener(
      "touchstart",
      (e) => {
        if (e.target.closest(".delete-btn") || e.target.closest(".edit-btn"))
          return;
        pressTimer = setTimeout(triggerEdit, 600); // 600мс удержания
      },
      { passive: true },
    );

    row.addEventListener("touchend", () => clearTimeout(pressTimer));
    row.addEventListener("touchmove", () => clearTimeout(pressTimer));

    // Клик по кнопке для ПК
    const editBtn = row.querySelector(".edit-btn");
    if (editBtn) {
      editBtn.addEventListener("click", triggerEdit);
    }
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
  // Очищаємо стандартні поля
  document.getElementById("searchInput").value = "";
  document.getElementById("daySelect").value = "";

  // Скидаємо візуальну частину кастомного селекта
  const wrapper = document.getElementById("filterDaySelectWrapper");
  if (wrapper) {
    const triggerSpan = wrapper.querySelector(".custom-select-trigger span");
    const options = wrapper.querySelectorAll(".custom-option");
    const defaultOption = wrapper.querySelector(
      '.custom-option[data-value=""]',
    );

    // Повертаємо початковий текст
    if (triggerSpan) triggerSpan.textContent = "Оберіть тему";

    // Знімаємо виділення з усіх опцій і ставимо на дефолтну
    options.forEach((opt) => opt.classList.remove("selected"));
    if (defaultOption) defaultOption.classList.add("selected");
  }

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
            `.delete-btn[data-id="${wordIdToDelete}"]`,
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
});

// ================= EDIT LOGIC =================
const editModal = document.getElementById("editModal");
const editForm = document.getElementById("editForm");

function openEditModal(row) {
  document.getElementById("editId").value = row.dataset.id;
  document.getElementById("editArticle").value = row.dataset.article;
  document.getElementById("editGerman").value = row.dataset.german;
  document.getElementById("editTranslation").value = row.dataset.translation;
  document.getElementById("editDayId").value = row.dataset.dayId;
  document.getElementById("editDescription").value = row.dataset.description;

  editModal.classList.add("active");
}

// Закрытие окна
document
  .getElementById("cancelEdit")
  .addEventListener("click", () => editModal.classList.remove("active"));
editModal.addEventListener("click", (e) => {
  if (e.target === editModal) editModal.classList.remove("active");
});

// Сохранение изменений без перезагрузки (AJAX)
editForm.addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch("", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.text())
    .then((res) => {
      if (res.trim() === "success") {
        editModal.classList.remove("active");

        // Обновляем строку в таблице визуально
        const id = formData.get("edit_id");
        const row = document.querySelector(`.word-row[data-id="${id}"]`);
        if (row) {
          // Обновляем data-атрибуты
          row.dataset.article = formData.get("article");
          row.dataset.german = formData.get("german");
          row.dataset.translation = formData.get("translation");
          row.dataset.dayId = formData.get("day_id");
          row.dataset.description = formData.get("description");

          // Обновляем текст
          row.querySelector(".article-cell").textContent =
            formData.get("article");

          row.children[3].textContent = formData.get("translation"); // ячейка перевода

          // Название темы из селекта
          const daySelect = document.getElementById("editDayId");
          const dayText = daySelect.options[daySelect.selectedIndex].text;
          row.querySelector(".day-cell").textContent = formData.get("day_id")
            ? dayText
            : "—";

          // Обновляем слово для озвучки
          const fullWord =
            (formData.get("article") ? formData.get("article") + " " : "") +
            formData.get("german");
          row.querySelector(".word-cell").dataset.word = fullWord;
        }
      } else {
        alert("Помилка збереження!");
      }
    });
});

/**
 * dictionary-accordion.js
 * Розгортає/згортає рядок деталей при кліку на рядок таблиці або кнопку ▾
 * Сумісний з існуючим dictionary.js (longpress → edit, click word → звук)
 */

(function () {
  // Делегуємо на tbody щоб працювало з динамічно доданими рядками (infinite scroll)
  const table = document.getElementById("wordsTable");
  if (!table) return;

  table.addEventListener("click", function (e) {
    const target = e.target;

    // Ігноруємо кліки по edit/delete — вони мають свій обробник
    if (target.closest(".edit-btn") || target.closest(".delete-btn")) return;

    // Ігноруємо кліки по слову — там озвучування (обробляється в dictionary.js)
    // Але якщо клік на ▾ — завжди розгортаємо
    const isExpandBtn = target.closest(".expand-btn");
    const row = target.closest(".word-row");
    if (!row) return;

    toggleAccordion(row);
  });

  function toggleAccordion(row) {
    const id = row.dataset.id;
    const accRow = table.querySelector(`.accordion-row[data-for="${id}"]`);
    if (!accRow) return;

    const expandBtn = row.querySelector(".expand-btn");
    const isOpen = accRow.classList.contains("open");

    // Закриваємо всі інші відкриті
    table.querySelectorAll(".accordion-row.open").forEach((r) => {
      r.classList.remove("open");
      const forId = r.dataset.for;
      const parentRow = table.querySelector(`.word-row[data-id="${forId}"]`);
      if (parentRow) {
        parentRow.classList.remove("expanded");
        const btn = parentRow.querySelector(".expand-btn");
        if (btn) btn.classList.remove("open");
      }
    });

    // Відкриваємо поточний (якщо він був закритий)
    if (!isOpen) {
      accRow.classList.add("open");
      row.classList.add("expanded");
      if (expandBtn) expandBtn.classList.add("open");

      // Плавний скрол до рядка якщо він за межами екрану
      setTimeout(() => {
        const rect = accRow.getBoundingClientRect();
        if (rect.bottom > window.innerHeight) {
          accRow.scrollIntoView({ behavior: "smooth", block: "nearest" });
        }
      }, 50);
    }
  }
})();
