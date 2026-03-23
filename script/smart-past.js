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

// =============================================================================
// AUTOCOMPLETE OFF — убираем браузерные подсказки на всех полях формы
// =============================================================================
document
  .querySelectorAll(
    'input[name="article"], input[name="german"], input[name="translation"], textarea[name="description"]',
  )
  .forEach((el) => {
    el.setAttribute("autocomplete", "new-password");
    el.setAttribute("autocorrect", "off");
    el.setAttribute("autocapitalize", "off");
    el.setAttribute("spellcheck", "false");
  });

// =============================================================================
// СЦЕНАРІЙ 4: Умляуты — панель кнопок
// Появляется НАД полем при фокусе на german/article/description
// Скрывается при blur и при открытии модалки вставки
// =============================================================================

const UMLAUT_MAP = [
  { label: "ä", value: "ä" },
  { label: "ö", value: "ö" },
  { label: "ü", value: "ü" },
  { label: "Ä", value: "Ä" },
  { label: "Ö", value: "Ö" },
  { label: "Ü", value: "Ü" },
  { label: "ß", value: "ß" },
];

// Одна плавающая панель на всю страницу
const umlautBar = document.createElement("div");
umlautBar.id = "umlaut-bar";
umlautBar.style.cssText = `
  position: absolute;
  display: none;
  gap: 4px;
  background: #1e1e2e;
  border: 1px solid #3f3f5a;
  border-radius: 8px;
  padding: 4px 6px;
  box-shadow: 0 -2px 12px rgba(0,0,0,0.35);
  z-index: 9999;
  align-items: center;
`;

// Иконка-метка
const umlautLabel = document.createElement("span");
umlautLabel.textContent = "Ä";
umlautLabel.style.cssText =
  "color: #6366f1; font-size: 11px; font-weight: 700; margin-right: 4px; opacity: 0.7; user-select:none;";
umlautBar.appendChild(umlautLabel);

UMLAUT_MAP.forEach(({ label, value }) => {
  const btn = document.createElement("button");
  btn.type = "button";
  btn.textContent = label;
  btn.title = value;
  btn.style.cssText = `
    background: #2d2d44;
    color: #e2e8f0;
    border: 1px solid #4a4a6a;
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.15s;
    line-height: 1.4;
  `;
  btn.addEventListener("mouseenter", () => (btn.style.background = "#4f46e5"));
  btn.addEventListener("mouseleave", () => (btn.style.background = "#2d2d44"));

  btn.addEventListener("mousedown", (e) => {
    // preventDefault — не снимаем фокус с поля
    e.preventDefault();
    const target = document.activeElement;
    if (
      target &&
      (target.name === "german" ||
        target.name === "article" ||
        target.name === "description")
    ) {
      const start = target.selectionStart;
      const end = target.selectionEnd;
      target.setRangeText(value, start, end, "end");
      target.dispatchEvent(new Event("input"));
    }
  });

  umlautBar.appendChild(btn);
});

document.body.appendChild(umlautBar);

/**
 * Показывает панель НАД полем.
 * Позиционируем по top = rect.top - высота панели - отступ.
 * Так панель не перекрывает поле и не мешает набору.
 */
function showUmlautBar(inputEl) {
  // Скрываем, если модалка вставки открыта
  if (spOverlay && spOverlay.classList.contains("active")) return;

  umlautBar.style.display = "flex";

  // Сначала показываем, чтобы получить реальную высоту панели
  requestAnimationFrame(() => {
    const rect = inputEl.getBoundingClientRect();
    const barH = umlautBar.offsetHeight || 34;
    const gap = 4;
    umlautBar.style.top = `${window.scrollY + rect.top - barH - gap}px`;
    umlautBar.style.left = `${window.scrollX + rect.left}px`;
  });
}

function hideUmlautBar() {
  umlautBar.style.display = "none";
}

// =============================================================================
// Утилита: убрать кавычки вокруг слова/фразы — СЦЕНАРІЙ 1
// =============================================================================
function stripQuotes(str) {
  if (!str) return str;
  // Убираем парные: "слово", 'слово', «слово», „слово", "слово"
  return str
    .trim()
    .replace(/^["'«„""](.+)["'»""]$/, "$1")
    .replace(/^["'](.+)["']$/, "$1")
    .trim();
}

// =============================================================================
// Утилита: извлечь транскрипцию [райф] из строки — СЦЕНАРІЙ 2
// Возвращает { clean, transcription }
// =============================================================================
function extractTranscription(str) {
  let transcription = "";
  // Ищем [...] или (кириллица...) — транскрипции обычно в квадратных скобках
  // Паттерн: блок в [] содержащий кириллические символы или спецзнаки транскрипции
  const transcRe = /\[([^\]]+)\]/g;
  let match;
  const results = [];
  while ((match = transcRe.exec(str)) !== null) {
    const inner = match[1].trim();
    // Считаем транскрипцией, если содержит кириллицу, дефис, апостроф или спецсимволы МФА
    if (/[а-яёА-ЯЁ'\-ˈˌ]/.test(inner)) {
      results.push({ full: match[0], inner });
    }
  }

  let clean = str;
  if (results.length > 0) {
    transcription = results.map((r) => `[${r.inner}]`).join(" ");
    results.forEach((r) => {
      clean = clean.replace(r.full, "").trim();
    });
  }

  return { clean, transcription };
}

// =============================================================================
// Утилита: извлечь пример в скобках с переводом — СЦЕНАРІЙ 3
// Формат: (Das Haus ist groß — Дом большой) или (Das Haus — Дом)
// Возвращает { clean, example }
// =============================================================================
function extractExample(str) {
  let example = "";
  // Ищем круглые скобки, содержащие тире/дефис (признак примера с переводом)
  // Пример должен содержать букву с заглавной (начало предложения)
  const exampleRe = /\(([^)]{10,})\)/g;
  let match;
  let found = null;
  while ((match = exampleRe.exec(str)) !== null) {
    const inner = match[1].trim();
    // Признак примера: содержит разделитель (тире или —) и заглавную букву
    if (/[-—–]/.test(inner) && /[A-ZА-ЯЁ]/.test(inner)) {
      found = { full: match[0], inner };
      break;
    }
  }

  let clean = str;
  if (found) {
    example = found.inner;
    clean = str.replace(found.full, "").trim();
  }

  return { clean, example };
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
  if (clean.startsWith("{")) {
    try {
      const obj = JSON.parse(clean);
      if (obj.german || obj.word) {
        return {
          article: (obj.article || "").substring(0, 10),
          word: stripQuotes((obj.german || obj.word || "").substring(0, 100)),
          translation: stripQuotes((obj.translation || "").substring(0, 200)),
          description: (obj.description || "").substring(0, 500),
        };
      }
    } catch (e) {
      // не JSON — продовжуємо
    }
  }

  // --- СЦЕНАРІЙ: Anki (крапка з комою) ---
  if (clean.includes(";")) {
    const parts = clean.split(";").map((p) => p.trim());
    if (parts.length >= 2 && parts[0] && parts[1]) {
      word = parts[0];
      translation = parts[1];
      description = parts.slice(2).join("; ");

      const articleMatch = word.match(/^(der|die|das|a|an|the)\s+(.+)$/i);
      if (articleMatch) {
        article = articleMatch[1].substring(0, 10);
        word = articleMatch[2];
      }

      return {
        article: article.substring(0, 10),
        word: stripQuotes(word.substring(0, 100)),
        translation: stripQuotes(translation.substring(0, 200)),
        description: description.substring(0, 500),
      };
    }
  }

  // --- СЦЕНАРІЙ 3: Пример в скобках (Das X ist Y — Перевод) ---
  // Извлекаем ДО разбора остального, чтобы пример не мешал парсингу слова
  {
    const exResult = extractExample(clean);
    if (exResult.example) {
      description = exResult.example;
      clean = exResult.clean;
    }
  }

  // --- СЦЕНАРІЙ: артикль у дужках перед словом ---
  const bracketArticleMatch = clean.match(
    /^[\[\(](der|die|das|a|an|the)[\]\)]\s*(.+)/i,
  );
  if (bracketArticleMatch) {
    article = bracketArticleMatch[1];
    clean = bracketArticleMatch[2].trim();
  }

  // --- СЦЕНАРІЙ 2: Транскрипция [райф] ---
  // Извлекаем из всей строки перед делением на слово/перевод
  {
    const transResult = extractTranscription(clean);
    if (transResult.transcription) {
      // Транскрипция идёт в description (если description пустой, иначе добавляем)
      if (!description) {
        description = transResult.transcription;
      } else {
        description = transResult.transcription + " | " + description;
      }
      clean = transResult.clean;
    }
  }

  // 2. Витягуємо опис з дужок () або [] (те, що залишилось після СЦЕНАРІЮ 3)
  clean = clean.replace(/[\(\[]([^)\]]+)[\)\]]/g, (match, p1) => {
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

  // --- СЦЕНАРІЙ 1: Убираем кавычки ---
  word = stripQuotes(word);
  translation = stripQuotes(translation);

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

    pasteQueue = parsed
      .slice(1)
      .map((p) => ({ ...p, raw: pasteText, inputObj: targetInput }));
    pendingPaste = { ...parsed[0], raw: pasteText, inputObj: targetInput };
    updateModalUI(pendingPaste);
    if (spOverlay) {
      hideUmlautBar();
      spOverlay.classList.add("active");
    }
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
  if (spOverlay) {
    hideUmlautBar();
    spOverlay.classList.add("active");
  }
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

  // --- СЦЕНАРІЙ 4: Умляуты — панель кнопок (german і article) ---
  if (input.name === "german" || input.name === "article") {
    input.addEventListener("focus", () => showUmlautBar(input));
    input.addEventListener("blur", () => setTimeout(hideUmlautBar, 150));
  }
});

// Умляуты для textarea[name="description"] — панель над полем
const descTextarea = document.querySelector('textarea[name="description"]');
if (descTextarea) {
  descTextarea.addEventListener("focus", () => showUmlautBar(descTextarea));
  descTextarea.addEventListener("blur", () => setTimeout(hideUmlautBar, 150));
}

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

    if (pasteQueue.length > 0) {
      const targetInput = document.querySelector('input[name="german"]');
      setTimeout(() => showNextFromQueue(targetInput), 150);
    }
  });
}

if (spCancel) {
  spCancel.addEventListener("click", () => {
    applyNormalPaste();
    pasteQueue = [];
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
