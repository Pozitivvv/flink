const modal = document.getElementById("deleteModal");
const cancelBtn = document.getElementById("cancelDelete");
const confirmBtn = document.getElementById("confirmDelete");

// ✅ ТОГГЛ "ДОДАТКОВО" (Оновлений з фіксом обрізки списків)
const advancedToggleBtn = document.getElementById("advancedToggleBtn");
const advancedContent = document.getElementById("advancedContent");
const advancedInner = advancedContent.querySelector(".advanced-inner");

if (advancedToggleBtn && advancedContent && advancedInner) {
  advancedToggleBtn.addEventListener("click", () => {
    const isOpen = advancedContent.classList.contains("open");

    if (isOpen) {
      advancedInner.style.overflow = "hidden";
      advancedToggleBtn.classList.remove("open");
      advancedContent.classList.remove("open");

      advancedContent
        .querySelectorAll(".custom-select-wrapper")
        .forEach((w) => w.classList.remove("open"));
    } else {
      advancedToggleBtn.classList.add("open");
      advancedContent.classList.add("open");

      setTimeout(() => {
        if (advancedContent.classList.contains("open")) {
          advancedInner.style.overflow = "visible";
        }
      }, 300);
    }
  });
}

// ✅ СЧЕТЧИК СИМВОЛОВ ДЛЯ ОПИСАНИЯ
const descInput = document.getElementById("wordDescription");
const charCount = document.getElementById("charCount");

if (descInput && charCount) {
  descInput.addEventListener("input", function () {
    const currentLength = this.value.length;
    charCount.textContent = `${currentLength} / 500`;

    if (currentLength >= 490) {
      charCount.style.color = "#ef4444";
      charCount.style.fontWeight = "bold";
    } else {
      charCount.style.color = "#6b6b6b";
      charCount.style.fontWeight = "normal";
    }
  });
}

// ✅ ЛОГИКА КАСТОМНЫХ СЕЛЕКТОВ
document.querySelectorAll(".custom-select-wrapper").forEach((wrapper) => {
  const trigger = wrapper.querySelector(".custom-select-trigger");
  const options = wrapper.querySelectorAll(".custom-option");
  const hiddenInput = wrapper.querySelector('input[type="hidden"]');
  const textSpan = trigger.querySelector("span");

  trigger.addEventListener("click", function (e) {
    document.querySelectorAll(".custom-select-wrapper").forEach((other) => {
      if (other !== wrapper) other.classList.remove("open");
    });
    wrapper.classList.toggle("open");
    e.stopPropagation();
  });

  options.forEach((option) => {
    option.addEventListener("click", function (e) {
      textSpan.textContent = this.textContent.trim();
      hiddenInput.value = this.getAttribute("data-value");

      options.forEach((opt) => opt.classList.remove("selected"));
      this.classList.add("selected");

      wrapper.classList.remove("open");
      e.stopPropagation();
    });
  });
});

document.addEventListener("click", function () {
  document.querySelectorAll(".custom-select-wrapper").forEach((wrapper) => {
    wrapper.classList.remove("open");
  });
});

// ✅ СМАРТ-ВСТАВКА (Smart Paste) UI
// ВИПРАВЛЕНО 1: Беремо тільки input, ігноруємо textarea (опис)
// Железобетонно выбираем только нужные поля по их name, игнорируя textarea
const smartInputs = document.querySelectorAll(
  'input[name="article"], input[name="german"], input[name="translation"]',
);
const spOverlay = document.getElementById("smartPasteOverlay");
const spApply = document.getElementById("smartPasteApply");
const spCancel = document.getElementById("smartPasteCancel");
const spClose = document.getElementById("smartPasteClose");
const quickPasteBtn = document.getElementById("quickPasteBtn");

let pendingPaste = null;

function closeSmartPaste() {
  if (spOverlay) spOverlay.classList.remove("active");
  pendingPaste = null;
}

function applyNormalPaste() {
  if (pendingPaste) {
    const el = pendingPaste.inputObj;
    // Ограничиваем длину при обычной вставке, если это слово или перевод
    const maxLength = el.name === "article" ? 10 : 200;
    const textToInsert = pendingPaste.raw.substring(0, maxLength);

    el.setRangeText(textToInsert, el.selectionStart, el.selectionEnd, "end");
  }
}

function handleSmartPaste(pasteText, targetInput, originalEvent = null) {
  // ВИПРАВЛЕНО 3: Якщо текст занадто довгий (більше 300 символів), це не словникова пара
  if (
    targetInput.tagName.toLowerCase() === "textarea" ||
    targetInput.name === "description"
  ) {
    return false;
  }
  if (pasteText.length > 300) {
    if (!originalEvent) {
      targetInput.setRangeText(
        pasteText.substring(0, 200),
        targetInput.selectionStart,
        targetInput.selectionEnd,
        "end",
      );
    }
    return false;
  }

  // ВИПРАВЛЕНО 2: Строга валідація
  // ^([^\-—–=]{1,80}) — ліва частина не містить тире/дорівнює і має довжину від 1 до 80 символів
  // \s*[-—–=]{1,3}\s* — розділювач може складатися максимум з 3 знаків тире або дорівнює
  // (.+)$ — права частина
  let match = pasteText.match(/^([^\-—–=]{1,80})\s*[-—–=]{1,3}\s*(.+)$/);

  if (match) {
    if (originalEvent) originalEvent.preventDefault();

    let leftPart = match[1].trim();
    let rightPart = match[2].trim();
    let article = "";
    let word = leftPart;

    let articleMatch = leftPart.match(/^(der|die|das|a|an|the)\s+(.+)$/i);
    if (articleMatch) {
      article = articleMatch[1].substring(0, 10);
      word = articleMatch[2].substring(0, 100); // Обмежуємо слово
    } else {
      word = word.substring(0, 100); // Обмежуємо слово
    }

    rightPart = rightPart.substring(0, 200); // Обмежуємо переклад

    pendingPaste = {
      article,
      word,
      translation: rightPart,
      raw: pasteText,
      inputObj: targetInput,
    };

    const artEl = document.getElementById("sp-article-text");
    if (article) {
      artEl.textContent = article;
      artEl.style.display = "block";
    } else {
      artEl.style.display = "none";
    }
    document.getElementById("sp-word-text").textContent = word;
    document.getElementById("sp-trans-text").textContent = rightPart;

    if (spOverlay) spOverlay.classList.add("active");
    return true;
  } else {
    if (!originalEvent) {
      targetInput.setRangeText(
        pasteText.substring(0, 200), // Обмежуємо вставку з кнопки
        targetInput.selectionStart,
        targetInput.selectionEnd,
        "end",
      );
    }
    return false;
  }
}

smartInputs.forEach((input) => {
  // Також додаємо обмеження вводу з клавіатури, щоб не можна було надрукувати "книгу"
  input.addEventListener("input", function () {
    const max = this.name === "article" ? 10 : 200;
    if (this.value.length > max) {
      this.value = this.value.substring(0, max);
    }
  });

  input.addEventListener("paste", function (e) {
    let pasteText = (e.clipboardData || window.clipboardData)
      .getData("text")
      .trim();
    handleSmartPaste(pasteText, this, e);
  });
});

if (quickPasteBtn) {
  quickPasteBtn.addEventListener("click", async () => {
    try {
      const text = await navigator.clipboard.readText();
      if (text) {
        handleSmartPaste(
          text.trim(),
          document.querySelector('input[name="german"]'),
        );
      }
    } catch (err) {
      console.error("Не вдалося прочитати буфер: ", err);
      alert("Будь ласка, дозвольте доступ до буфера обміну у вашому браузері.");
    }
  });
}

if (spApply) {
  spApply.addEventListener("click", () => {
    if (pendingPaste) {
      document.querySelector('input[name="article"]').value =
        pendingPaste.article;
      document.querySelector('input[name="german"]').value = pendingPaste.word;
      document.querySelector('input[name="translation"]').value =
        pendingPaste.translation;
    }
    closeSmartPaste();
  });
}

if (spCancel) {
  spCancel.addEventListener("click", () => {
    applyNormalPaste();
    closeSmartPaste();
  });
}

if (spClose) {
  spClose.addEventListener("click", () => {
    applyNormalPaste();
    closeSmartPaste();
  });
}

if (spOverlay) {
  spOverlay.addEventListener("click", (e) => {
    if (e.target === spOverlay) {
      applyNormalPaste();
      closeSmartPaste();
    }
  });
}

// ✅ AJAX обработка
let wordIdToDelete = null;
const messageContainer = document.getElementById("message-container");
const addWordForm = document.getElementById("addWordForm");

if (addWordForm) {
  addWordForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("ajax_add", "1");

    fetch("", {
      method: "POST",
      body: formData,
    })
      .then((r) => r.json())
      .then((data) => {
        showMessage(data.message, data.status);
        if (data.status === "success") {
          this.reset();

          // Сбрасываем кастомные селекты на значения по умолчанию
          document
            .querySelectorAll(".custom-select-wrapper")
            .forEach((wrapper) => {
              const triggerSpan = wrapper.querySelector(
                ".custom-select-trigger span",
              );
              const hiddenInput = wrapper.querySelector('input[type="hidden"]');
              const defaultOption = wrapper.querySelector(
                '.custom-option[data-value=""]',
              );

              if (defaultOption && triggerSpan && hiddenInput) {
                triggerSpan.textContent = defaultOption.textContent.trim();
                hiddenInput.value = "";
                wrapper
                  .querySelectorAll(".custom-option")
                  .forEach((opt) => opt.classList.remove("selected"));
                defaultOption.classList.add("selected");
              }
            });

          // Сбрасываем счетчик символов
          if (charCount) {
            charCount.textContent = "0 / 500";
            charCount.style.color = "#6b6b6b";
            charCount.style.fontWeight = "normal";
          }

          setTimeout(() => location.reload(), 2000);
        }
      });
  });
}

function showMessage(msg, status) {
  if (!messageContainer) return;
  const message = document.createElement("div");
  message.className = `message ${status}`;
  message.textContent = msg;
  messageContainer.innerHTML = "";
  messageContainer.appendChild(message);
}

function goBack() {
  window.history.back();
}

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
      if (modal) modal.classList.add("active");
    });
  });
}

// Закрыть модальное окно
if (cancelBtn) {
  cancelBtn.addEventListener("click", () => {
    if (modal) modal.classList.remove("active");
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
// Подтвердить удаление
if (confirmBtn) {
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
            // Находим главную строку и строку-аккордеон
            const row = document.querySelector(
              `.word-row[data-id="${wordIdToDelete}"]`,
            );
            const accRow = document.querySelector(
              `.accordion-row[data-for="${wordIdToDelete}"]`,
            );

            if (row) {
              row.style.transition = "all 0.3s ease";
              row.style.opacity = "0";
              row.style.transform = "translateX(-20px)";
              if (accRow) accRow.style.opacity = "0";

              setTimeout(() => {
                row.remove();
                if (accRow) accRow.remove();

                // Проверяем, остались ли слова в таблице
                const remainingWords = document.querySelectorAll(".word-row");
                if (remainingWords.length === 0) {
                  const tbody = document.querySelector("#wordsTable tbody");
                  if (tbody) {
                    tbody.innerHTML = `
                      <tr>
                        <td colspan="4" style="text-align: center; color: #6b6b6b; padding: 40px 20px; animation: fadeIn 0.3s ease;">
                          Поки що немає слів у цій темі.
                        </td>
                      </tr>
                    `;
                  }
                }
              }, 300);
            }

            // Закрываем модалку
            const modal = document.getElementById("deleteModal");
            if (modal) modal.classList.remove("active");
            wordIdToDelete = null;
          }
        });
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

// Підключаємо події після загрузки
document.addEventListener("DOMContentLoaded", function () {
  attachDeleteEvents();
  attachSoundEvents();
});
// ================= EDIT & ACCORDION & AUDIO LOGIC (PORTED FROM DICTIONARY) =================

const editModal = document.getElementById("editModal");
const editForm = document.getElementById("editForm");
const wordsTable = document.getElementById("wordsTable");

if (wordsTable) {
  wordsTable.addEventListener("click", function (e) {
    const target = e.target;
    const row = target.closest(".word-row");
    if (!row) return;

    // 1. Клик по кнопке "Редактировать"
    if (target.closest(".edit-btn")) {
      openEditModal(row);
      return;
    }

    // 2. Клик по кнопке "Удалить" (уже обрабатывается вашим старым кодом,
    // но если нужно делегировать, можно добавить сюда)
    if (target.closest(".delete-btn")) {
      wordIdToDelete = target.closest(".delete-btn").dataset.id;
      document.getElementById("deleteModal").classList.add("active");
      return;
    }

    // 3. Клик по слову (Озвучка)
    if (target.closest(".word-cell") && !target.closest(".expand-btn")) {
      const cell = target.closest(".word-cell");
      const word = cell.dataset.word;

      const textSpan = cell.querySelector(".word-text");
      if (textSpan) {
        textSpan.style.opacity = "0.5";
        setTimeout(() => (textSpan.style.opacity = "1"), 200);
      }
      playWord(word);
    }

    // 4. Аккордеон (открывается при клике на строку или стрелочку)
    if (!target.closest(".action-cell") || target.closest(".expand-btn")) {
      toggleAccordion(row);
    }
  });
}

function toggleAccordion(row) {
  const id = row.dataset.id;
  const accRow = wordsTable.querySelector(`.accordion-row[data-for="${id}"]`);
  if (!accRow) return;

  const expandBtn = row.querySelector(".expand-btn");
  const isOpen = accRow.classList.contains("open");

  // Закрываем другие
  wordsTable.querySelectorAll(".accordion-row.open").forEach((r) => {
    r.classList.remove("open");
    const parentRow = wordsTable.querySelector(
      `.word-row[data-id="${r.dataset.for}"]`,
    );
    if (parentRow) {
      parentRow.classList.remove("expanded");
      const btn = parentRow.querySelector(".expand-btn");
      if (btn) btn.classList.remove("open");
    }
  });

  if (!isOpen) {
    accRow.classList.add("open");
    row.classList.add("expanded");
    if (expandBtn) expandBtn.classList.add("open");
  }
}

// Открытие модалки редактирования
function openEditModal(row) {
  document.getElementById("editId").value = row.dataset.id;
  document.getElementById("editArticle").value = row.dataset.article;
  document.getElementById("editGerman").value = row.dataset.german;
  document.getElementById("editTranslation").value = row.dataset.translation;
  document.getElementById("editDescription").value = row.dataset.description;

  // Кастомный селект для "Типа"
  const typeValue = row.dataset.type;
  document.getElementById("editType").value = typeValue;

  const wrapper = document.getElementById("editTypeSelectWrapper");
  const triggerSpan = wrapper.querySelector(".custom-select-trigger span");
  const targetOption = wrapper.querySelector(
    `.custom-option[data-value="${typeValue}"]`,
  );

  wrapper
    .querySelectorAll(".custom-option")
    .forEach((opt) => opt.classList.remove("selected"));
  if (targetOption) {
    triggerSpan.textContent = targetOption.textContent.trim();
    targetOption.classList.add("selected");
  } else {
    triggerSpan.textContent = "— Частина мови —";
  }

  editModal.classList.add("active");
}

// Закрытие модалки
document
  .getElementById("cancelEdit")
  ?.addEventListener("click", () => editModal.classList.remove("active"));
editModal?.addEventListener("click", (e) => {
  if (e.target === editModal) editModal.classList.remove("active");
});

// Сохранение изменений (AJAX)
editForm?.addEventListener("submit", function (e) {
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

        // Перезагрузка страницы для обновления таблицы и аккордеонов
        // (самый надежный вариант для add_word.php)
        location.reload();
      } else {
        alert("Помилка збереження!");
      }
    });
});
