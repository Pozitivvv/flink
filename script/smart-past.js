// smart-paste.js

const smartInputs = document.querySelectorAll(
  'input[name="article"], input[name="german"], input[name="translation"]',
);
const spOverlay = document.getElementById("smartPasteOverlay");
const spApply = document.getElementById("smartPasteApply");
const spCancel = document.getElementById("smartPasteCancel");
const spClose = document.getElementById("smartPasteClose");
const quickPasteBtn = document.getElementById("quickPasteBtn");

let pendingPaste = null;

// Очередь для многострочной вставки
let pasteQueue = [];

// Динамічно створюємо елемент для показу опису в модальному вікні
let spDescText = document.getElementById("sp-desc-text");
if (!spDescText && spOverlay) {
  const contentBox = spOverlay.querySelector(".sp-content-box");
  if (contentBox) {
    spDescText = document.createElement("p");
    spDescText.id = "sp-desc-text";
    spDescText.style.cssText =
      "color: #10b981; font-size: 13px; margin-top: 8px; font-style: italic; display: none;";
    contentBox.appendChild(spDescText);
  }
}

// Индикатор очереди (показывает "ещё N слов в очереди")
let spQueueText = document.getElementById("sp-queue-text");
if (!spQueueText && spOverlay) {
  const contentBox = spOverlay.querySelector(".sp-content-box");
  if (contentBox) {
    spQueueText = document.createElement("p");
    spQueueText.id = "sp-queue-text";
    spQueueText.style.cssText =
      "color: #6366f1; font-size: 12px; margin-top: 6px; font-style: italic; display: none;";
    contentBox.appendChild(spQueueText);
  }
}

function closeSmartPaste() {
  if (spOverlay) spOverlay.classList.remove("active");
  pendingPaste = null;
}

function applyNormalPaste() {
  if (pendingPaste) {
    const el = pendingPaste.inputObj;
    const maxLength = el.name === "article" ? 10 : 200;
    const textToInsert = pendingPaste.raw.substring(0, maxLength);
    el.setRangeText(textToInsert, el.selectionStart, el.selectionEnd, "end");
  }
}

/**
 * Заполняет форму из объекта { article, word, translation, description }
 */
function fillFormFromParsed(parsed) {
  document.querySelector('input[name="article"]').value = parsed.article || "";
  document.querySelector('input[name="german"]').value = parsed.word || "";
  document.querySelector('input[name="translation"]').value =
    parsed.translation || "";

  const descInput = document.querySelector('textarea[name="description"]');
  if (descInput && parsed.description) {
    descInput.value = parsed.description;
    descInput.dispatchEvent(new Event("input"));

    const advancedContent = document.getElementById("advancedContent");
    const advancedToggleBtn = document.getElementById("advancedToggleBtn");
    if (advancedContent && !advancedContent.classList.contains("open")) {
      advancedToggleBtn.click();
    }
  }
}

/**
 * Показывает следующий элемент очереди в модалке, если очередь не пуста.
 */
function showNextFromQueue(targetInput) {
  if (pasteQueue.length === 0) return;
  const next = pasteQueue.shift();
  pendingPaste = { ...next, inputObj: targetInput };
  updateModalUI(pendingPaste);
  if (spOverlay) spOverlay.classList.add("active");
}

function updateModalUI(paste) {
  const artEl = document.getElementById("sp-article-text");
  if (paste.article) {
    artEl.textContent = paste.article;
    artEl.style.display = "block";
  } else {
    artEl.style.display = "none";
  }
  document.getElementById("sp-word-text").textContent = paste.word;
  document.getElementById("sp-trans-text").textContent = paste.translation;

  if (spDescText) {
    if (paste.description) {
      spDescText.textContent = "Опис: " + paste.description;
      spDescText.style.display = "block";
    } else {
      spDescText.style.display = "none";
    }
  }

  if (spQueueText) {
    if (pasteQueue.length > 0) {
      spQueueText.textContent = `Ще ${pasteQueue.length} слів у черзі`;
      spQueueText.style.display = "block";
    } else {
      spQueueText.style.display = "none";
    }
  }
}

/**
 * Парсит одну строку текста.
 * Возвращает объект { article, word, translation, description } или null.
 */
function parseSingleLine(text) {
  let word = "";
  let translation = "";
  let description = "";
  let article = "";

  // 1. Очищення нумерації
  let clean = text.replace(/^\d+[\.\)]\s*/, "").trim();

  // --- СЦЕНАРІЙ: JSON ---
  // Підтримка: {"german":"...","translation":"...","article":"...","description":"..."}
  if (clean.startsWith("{")) {
    try {
      const obj = JSON.parse(clean);
      if (obj.german || obj.word) {
        return {
          article: (obj.article || "").substring(0, 10),
          word: (obj.german || obj.word || "").substring(0, 100),
          translation: (obj.translation || "").substring(0, 200),
          description: (obj.description || "").substring(0, 500),
        };
      }
    } catch (e) {
      // не JSON — продовжуємо
    }
  }

  // --- СЦЕНАРІЙ: Anki (крапка з комою) ---
  // Формат: слово;переклад;опис
  if (clean.includes(";")) {
    const parts = clean.split(";").map((p) => p.trim());
    if (parts.length >= 2 && parts[0] && parts[1]) {
      word = parts[0];
      translation = parts[1];
      description = parts.slice(2).join("; ");

      // Перевіряємо артикль у слові
      const articleMatch = word.match(/^(der|die|das|a|an|the)\s+(.+)$/i);
      if (articleMatch) {
        article = articleMatch[1].substring(0, 10);
        word = articleMatch[2];
      }

      return {
        article: article.substring(0, 10),
        word: word.substring(0, 100),
        translation: translation.substring(0, 200),
        description: description.substring(0, 500),
      };
    }
  }

  // --- СЦЕНАРІЙ: артикль у дужках перед словом ---
  // Формат: [die] Katze — кот  або  (der) Hund — пёс
  const bracketArticleMatch = clean.match(
    /^[\[\(](der|die|das|a|an|the)[\]\)]\s*(.+)/i,
  );
  if (bracketArticleMatch) {
    article = bracketArticleMatch[1];
    clean = bracketArticleMatch[2].trim();
    // Далі парсимо решту як звичайний рядок (слово — переклад)
  }

  // 2. Витягуємо опис з дужок () або []
  clean = clean.replace(/[\(\[]([^)\]]+)[\)\]]/g, (match, p1) => {
    // Не витягуємо, якщо це вже артикль (вже оброблено вище)
    const inner = p1.trim().toLowerCase();
    if (!article && /^(der|die|das|a|an|the)$/i.test(inner)) {
      article = p1.trim();
      return "";
    }
    if (!description) description = p1.trim();
    return "";
  });

  clean = clean.replace(/\s+\./g, "").replace(/\.$/, "").trim();

  // 3. Розділення по табуляції (Excel)
  if (clean.includes("\t")) {
    const parts = clean
      .split("\t")
      .map((p) => p.trim())
      .filter((p) => p);
    if (parts.length >= 2) {
      word = parts[0];
      translation = parts[1];
      if (parts.length >= 3 && !description)
        description = parts.slice(2).join(" ");
    }
  } else {
    // Ділимо по тире, дорівнює, двокрапці, вертикальній рисці
    const parts = clean
      .split(/(?:\s+[-—–=]+\s+)|(?:\s*[:|]\s*)/)
      .filter((p) => p.trim());

    if (parts.length >= 2) {
      word = parts[0];
      translation = parts[1];
      if (parts.length >= 3 && !description) {
        description = parts.slice(2).join(" - ");
      }
    }
  }

  if (!word || !translation) return null;

  // Відокремлення артикля від слова (якщо ще не знайдено)
  if (!article) {
    const articleMatch = word.match(/^(der|die|das|a|an|the)\s+(.+)$/i);
    if (articleMatch) {
      article = articleMatch[1];
      word = articleMatch[2];
    }
  }

  return {
    article: article.substring(0, 10),
    word: word.trim().substring(0, 100),
    translation: translation.trim().substring(0, 200),
    description: description.trim().substring(0, 500),
  };
}

function handleSmartPaste(pasteText, targetInput, originalEvent = null) {
  // Ігноруємо textarea (опис)
  if (
    targetInput.tagName.toLowerCase() === "textarea" ||
    targetInput.name === "description"
  ) {
    return false;
  }

  if (pasteText.length > 600) {
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

  // --- СЦЕНАРІЙ: багаторядкова вставка ---
  const lines = pasteText
    .split(/\r?\n/)
    .map((l) => l.trim())
    .filter((l) => l.length > 0);

  if (lines.length > 1) {
    if (originalEvent) originalEvent.preventDefault();

    const parsed = lines.map(parseSingleLine).filter(Boolean);
    if (parsed.length === 0) {
      // Нічого не розпізнано — звичайна вставка першого рядка
      if (!originalEvent) {
        targetInput.setRangeText(
          lines[0].substring(0, 200),
          targetInput.selectionStart,
          targetInput.selectionEnd,
          "end",
        );
      }
      return false;
    }

    // Перший елемент — в модалку, решта — в чергу
    pasteQueue = parsed
      .slice(1)
      .map((p) => ({ ...p, raw: pasteText, inputObj: targetInput }));
    pendingPaste = { ...parsed[0], raw: pasteText, inputObj: targetInput };
    updateModalUI(pendingPaste);
    if (spOverlay) spOverlay.classList.add("active");
    return true;
  }

  // --- Однорядкова вставка ---
  const parsed = parseSingleLine(pasteText);

  if (!parsed) {
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

  if (originalEvent) originalEvent.preventDefault();

  pasteQueue = [];
  pendingPaste = { ...parsed, raw: pasteText, inputObj: targetInput };
  updateModalUI(pendingPaste);
  if (spOverlay) spOverlay.classList.add("active");
  return true;
}

smartInputs.forEach((input) => {
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
      fillFormFromParsed(pendingPaste);
    }
    closeSmartPaste();

    // Якщо в черзі ще є слова — показуємо наступне
    if (pasteQueue.length > 0) {
      const targetInput = document.querySelector('input[name="german"]');
      // Невелика затримка, щоб модалка встигла закритись
      setTimeout(() => showNextFromQueue(targetInput), 150);
    }
  });
}

if (spCancel) {
  spCancel.addEventListener("click", () => {
    applyNormalPaste();
    pasteQueue = []; // Скасовуємо всю чергу
    closeSmartPaste();
  });
}

if (spClose) {
  spClose.addEventListener("click", () => {
    applyNormalPaste();
    pasteQueue = [];
    closeSmartPaste();
  });
}

if (spOverlay) {
  spOverlay.addEventListener("click", (e) => {
    if (e.target === spOverlay) {
      applyNormalPaste();
      pasteQueue = [];
      closeSmartPaste();
    }
  });
}
