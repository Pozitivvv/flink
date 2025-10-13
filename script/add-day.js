// Змінна для збереження ID теми, яку треба видалити
let deleteThemeId = null;

// Створюємо модальне вікно
function createModal() {
  const modalHTML = `
    <div class="modal-overlay" id="deleteModal">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-icon">🗑️</div>
          <h2>Видалити тему?</h2>
          <p>Ця дія незворотна. Всі слова цієї теми будуть також видалені.</p>
        </div>
        <div class="modal-buttons">
          <button class="modal-btn modal-btn-cancel" onclick="closeModal()">Скасувати</button>
          <button class="modal-btn modal-btn-delete" onclick="confirmDelete()">Видалити</button>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);
}

// Відкриваємо модальне вікно
function openModal(themeId) {
  deleteThemeId = themeId;
  const modal = document.getElementById("deleteModal");
  if (modal) {
    modal.classList.add("active");
  }
}

// Закриваємо модальне вікно
function closeModal() {
  const modal = document.getElementById("deleteModal");
  if (modal) {
    modal.classList.remove("active");
  }
  deleteThemeId = null;
}

// Підтверджуємо видалення
function confirmDelete() {
  if (!deleteThemeId) return;

  // AJAX запит на видалення
  const formData = new FormData();
  formData.append("delete_id", deleteThemeId);

  fetch("add_day.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Анімація видалення
        const item = document.querySelector(`[data-id="${deleteThemeId}"]`);
        if (item) {
          item.style.opacity = "0";
          item.style.transform = "translateX(-20px)";
          setTimeout(() => {
            item.remove();

            // Перевіряємо, чи залишились теми
            const themeList = document.getElementById("themeList");
            if (themeList && themeList.children.length === 0) {
              themeList.outerHTML =
                '<p style="color:#6b6b6b;">Ще не додано жодного уроку.</p>';
            }
          }, 300);
        }
        closeModal();
      }
    })
    .catch((error) => {
      console.error("Помилка:", error);
      alert("Сталася помилка при видаленні теми");
      closeModal();
    });
}

// Закриття модалки при кліку поза нею
document.addEventListener("click", function (e) {
  const modal = document.getElementById("deleteModal");
  if (modal && e.target === modal) {
    closeModal();
  }
});

// Закриття модалки по ESC
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    closeModal();
  }
});

// Ініціалізація
document.addEventListener("DOMContentLoaded", function () {
  // Створюємо модальне вікно
  createModal();

  // Додаємо обробники до кнопок видалення
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      const themeItem = this.closest(".theme-item");
      const themeId = themeItem.dataset.id;
      openModal(themeId);
    });
  });

  // Активний стан навігації (тільки для мобільних)
  document.querySelectorAll(".nav-item").forEach((item) => {
    item.addEventListener("click", function (e) {
      document.querySelectorAll(".nav-item").forEach((nav) => {
        nav.classList.remove("active");
      });
      this.classList.add("active");
    });
  });
});
