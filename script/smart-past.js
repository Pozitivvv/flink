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
let pasteQueue = [];

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

const UMLAUT_MAP = [
  { label: "ä", value: "ä" },
  { label: "ö", value: "ö" },
  { label: "ü", value: "ü" },
  { label: "Ä", value: "Ä" },
  { label: "Ö", value: "Ö" },
  { label: "Ü", value: "Ü" },
  { label: "ß", value: "ß" },
];

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

function showUmlautBar(inputEl) {
  if (spOverlay && spOverlay.classList.contains("active")) return;
  umlautBar.style.display = "flex";
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

function stripQuotes(str) {
  if (!str) return str;
  return str
    .trim()
    .replace(/^["'«„""](.+)["'»""]$/, "$1")
    .replace(/^["'](.+)["']$/, "$1")
    .trim();
}

function extractTranscription(str) {
  let transcription = "";
  const transcRe = /\[([^\]]+)\]/g;
  let match;
  const results = [];
  while ((match = transcRe.exec(str)) !== null) {
    const inner = match[1].trim();
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

function extractExample(str) {
  let example = "";
  const exampleRe = /\(([^)]{10,})\)/g;
  let match;
  let found = null;
  while ((match = exampleRe.exec(str)) !== null) {
    const inner = match[1].trim();
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
    } catch (e) {}
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

  // --- СЦЕНАРІЙ 5: слово+перевод слиплись ---
  // Поддерживаемые форматы:
  //   arbeitenработать
  //   arbeitenработатьIch arbeite heute. (Я сегодня работаю)
  //   ÜberweisenПереводить (деньги)Ich überweise die Miete. (Я перевожу арендную плату)
  //   ÜbermorgenПослезавтраWir treffen uns übermorgen. (Мы встретимся послезавтра)
  //   Die PrüfungПроверка / КонтрольDie Prüfung der Rechnung dauert kurz. (Проверка счета)
  {
    // Сначала пробуем вычленить артикль в начале (слиплось с заглавной буквы)
    // Формат: DerBaum... / DasBonbon... / DiePrüfung...
    // Только если сразу после артикля идёт заглавная латиница (начало слова)
    const articleGluedRe =
      /^(Der|Die|Das)\s*([A-ZÄÖÜ][A-Za-zÄÖÜäöüß]*)([\s\S]*)$/;
    const agMatch = clean.match(articleGluedRe);
    if (agMatch) {
      article = agMatch[1].toLowerCase(); // der/die/das
      clean = agMatch[2] + agMatch[3];
    }

    // Шаг 1: вычленяем латинское слово (до первой кириллицы)
    const latinWordRe = /^([A-Za-zÀ-ɏÄÖÜäöüß]+)([\s\S]*)$/;
    const lwMatch = clean.match(latinWordRe);
    if (lwMatch) {
      const candidateWord = lwMatch[1];
      const rest = lwMatch[2];

      // Шаг 2: rest должен начинаться с кириллицы
      if (/^[Ѐ-ӿА-Яа-яёЁ]/.test(rest)) {
        // Шаг 3: вычленяем кириллический перевод до начала примера.
        // Идём посимвольно, считаем глубину скобок.
        // Как только встречаем заглавную латиницу вне скобок после
        // хотя бы одного кирилл. символа — это начало примера.
        let translationRaw = "";
        let exampleRaw = "";
        let depth = 0;
        let cyrillicSeen = false;
        let i = 0;

        while (i < rest.length) {
          const ch = rest[i];
          if (ch === "(" || ch === "[") depth++;
          else if (ch === ")" || ch === "]") {
            depth--;
            if (depth < 0) depth = 0;
          }

          if (/[Ѐ-ӿА-Яа-яёЁ]/.test(ch)) cyrillicSeen = true;

          // Граница: заглавная латиница вне скобок после хотя бы одного кирилл. символа
          // Предыдущий символ: пробел/тире/слэш, закрывающая скобка,
          // или прямой переход кириллица→латиница (без пробела)
          const prevCh = rest[i - 1] || "";
          const prevIsBoundary =
            /[\s\-—–\/\)\]]/.test(prevCh) || /[Ѐ-ӿА-Яа-яёЁ]/.test(prevCh);
          if (
            depth === 0 &&
            cyrillicSeen &&
            /[A-ZÄÖÜ]/.test(ch) &&
            i > 0 &&
            prevIsBoundary
          ) {
            exampleRaw = rest.slice(i).trim();
            break;
          }

          translationRaw += ch;
          i++;
        }

        if (!exampleRaw) translationRaw = rest;
        translationRaw = translationRaw.trim();
        exampleRaw = exampleRaw.trim();

        if (translationRaw && /[Ѐ-ӿА-Яа-яёЁ]/.test(translationRaw)) {
          word = candidateWord;
          translation = translationRaw;
          if (exampleRaw && !description) description = exampleRaw;

          return {
            article: article.substring(0, 10),
            word: stripQuotes(word.substring(0, 100)),
            translation: stripQuotes(translation.substring(0, 200)),
            description: description.substring(0, 500),
          };
        }
      }
    }

    // Если артикль был извлечён но слово не смогли разобрать — сбрасываем clean
    if (article) {
      const agMatchOrig = text
        .replace(/^\d+[\.\)]\s*/, "")
        .trim()
        .match(articleGluedRe);
      if (agMatchOrig) {
        clean = text.replace(/^\d+[\.\)]\s*/, "").trim();
        article = "";
      }
    }
  }

  // --- СЦЕНАРІЙ 6: слово (перевод в скобках) — пример (перевод примера) ---
  // Формат: prüfen (проверять) — Ich prüfe das. (Я проверяю это)
  {
    const sc6 = clean.match(
      /^([A-Za-zÄÖÜäöüß]+)\s*\(([^)]+)\)\s*[-—–]\s*(.+)$/,
    );
    if (sc6) {
      word = sc6[1].trim();
      translation = sc6[2].trim();
      description = sc6[3].trim();
      return {
        article: article.substring(0, 10),
        word: stripQuotes(word.substring(0, 100)),
        translation: stripQuotes(translation.substring(0, 200)),
        description: description.substring(0, 500),
      };
    }
  }

  // --- СЦЕНАРІЙ 7: слово (кириллический перевод) без тире ---
  // Формат: sehen (видеть)  |  der Baum (дерево)
  {
    const sc7 = clean.match(
      /^(?:(der|die|das)\s+)?([A-Za-zÄÖÜäöüß]+)\s+\(([Ѐ-ӿА-Яа-яёЁ][^)]*)\)\s*(.*)$/i,
    );
    if (sc7) {
      article = (sc7[1] || "").trim();
      word = sc7[2].trim();
      translation = sc7[3].trim();
      const rest = sc7[4].trim();
      if (rest && !description) description = rest;

      return {
        article: article.substring(0, 10),
        word: stripQuotes(word.substring(0, 100)),
        translation: stripQuotes(translation.substring(0, 200)),
        description: description.substring(0, 500),
      };
    }
  }

  // --- СЦЕНАРІЙ 2б: только артикль + слово (без перевода) ---
  // Формат: "das Bonbon", "der Baum", "die Katze"
  // Срабатывает ТОЛЬКО если строка = ровно артикль + одно слово с заглавной
  // (защита от ложных срабатываний на обычные фразы)
  {
    const articleOnlyRe = /^(der|die|das)\s+([A-ZÄÖÜ][A-Za-zÄÖÜäöüß]+)$/i;
    const aoMatch = clean.match(articleOnlyRe);
    if (aoMatch) {
      return {
        article: aoMatch[1].toLowerCase().substring(0, 10),
        word: aoMatch[2].substring(0, 100),
        translation: "",
        description: "",
      };
    }
  }

  // --- СЦЕНАРІЙ 3: Пример в скобках (Das X ist Y — Перевод) ---
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
  {
    const transResult = extractTranscription(clean);
    if (transResult.transcription) {
      if (!description) {
        description = transResult.transcription;
      } else {
        description = transResult.transcription + " | " + description;
      }
      clean = transResult.clean;
    }
  }

  // 2. Витягуємо опис з дужок () або []
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

  // Відокремлення артикля від слова
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

  if (input.name === "german" || input.name === "article") {
    input.addEventListener("focus", () => showUmlautBar(input));
    input.addEventListener("blur", () => setTimeout(hideUmlautBar, 150));
  }
});

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

// =============================================================================
// АВТОСКОБКИ — вставка парных символов как в VSCode
// Работает для полей: german, article, translation, description
// =============================================================================
const AUTO_PAIRS = {
  "(": ")",
  "[": "]",
  '"': '"',
  "'": "'",
};

const autoParenFields = document.querySelectorAll(
  'input[name="article"], input[name="german"], input[name="translation"], textarea[name="description"]',
);

autoParenFields.forEach((el) => {
  el.addEventListener("keydown", function (e) {
    const open = e.key;
    const close = AUTO_PAIRS[open];
    if (!close) return;

    const start = this.selectionStart;
    const end = this.selectionEnd;
    const value = this.value;
    const selected = value.slice(start, end);

    // Если есть выделение — оборачиваем его в скобки
    if (selected.length > 0) {
      e.preventDefault();
      const newValue =
        value.slice(0, start) + open + selected + close + value.slice(end);
      this.value = newValue;
      this.setSelectionRange(start + 1, end + 1);
      this.dispatchEvent(new Event("input"));
      return;
    }

    // Курсор перед закрывающей скобкой — пропрыгиваем через неё
    if (value[start] === close) {
      e.preventDefault();
      this.setSelectionRange(start + 1, start + 1);
      return;
    }

    // Вставляем пару, курсор между ними
    e.preventDefault();
    const newValue = value.slice(0, start) + open + close + value.slice(end);
    this.value = newValue;
    this.setSelectionRange(start + 1, start + 1);
    this.dispatchEvent(new Event("input"));
  });

  // Backspace: если слева открывающая и справа закрывающая — удаляем оба
  el.addEventListener("keydown", function (e) {
    if (e.key !== "Backspace") return;
    const start = this.selectionStart;
    const end = this.selectionEnd;
    if (start !== end) return;
    const value = this.value;
    const left = value[start - 1];
    const right = value[start];
    if (left && right && AUTO_PAIRS[left] === right) {
      e.preventDefault();
      this.value = value.slice(0, start - 1) + value.slice(start + 1);
      this.setSelectionRange(start - 1, start - 1);
      this.dispatchEvent(new Event("input"));
    }
  });
});
