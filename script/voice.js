// voice.js
console.log("on voice");

// Получаем язык из глобальной переменной (которую мы зададим через PHP).
// Если переменная не задана, по умолчанию используем немецкий ('de').
function getLearningLang() {
  return window.LEARNING_LANGUAGE || "de";
}

function playWord(word, langCode = getLearningLang()) {
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

  if (isIOS) {
    const wordWithoutArticle = stripArticle(word, langCode);

    // Если это связка слов (есть пробел внутри), сразу используем встроенный голос.
    if (wordWithoutArticle.includes(" ")) {
      fallbackPlayWord(word, langCode);
    } else {
      // Для одиночных слов пробуем Wikimedia
      playWithWikimedia(word, wordWithoutArticle, langCode);
    }
  } else {
    // Для Android/ПК всегда встроенный синтез
    fallbackPlayWord(word, langCode);
  }
}

// Теперь функция учитывает язык при удалении артиклей
function stripArticle(word, langCode) {
  // База артиклей для популярных языков
  const articlesMap = {
    de: [
      "der",
      "die",
      "das",
      "den",
      "dem",
      "des",
      "denen",
      "einen",
      "einem",
      "einer",
      "eines",
      "ein",
    ],
    en: ["the", "a", "an"],
    es: ["el", "la", "los", "las", "un", "una", "unos", "unas"],
    fr: ["le", "la", "les", "l'", "un", "une", "des"],
    it: ["il", "lo", "la", "i", "gli", "le", "l'", "un", "uno", "una", "un'"],
  };

  const articles = articlesMap[langCode.toLowerCase()] || [];
  let cleanedWord = word.trim();
  const lowerWord = cleanedWord.toLowerCase();

  for (let article of articles) {
    // Учитываем языки с апострофами (например, французское l'arbre)
    if (article.endsWith("'")) {
      if (lowerWord.startsWith(article)) {
        cleanedWord = cleanedWord.substring(article.length).trim();
        break;
      }
    } else {
      // Обычные артикли с пробелом
      if (lowerWord.startsWith(article + " ")) {
        cleanedWord = cleanedWord.substring(article.length + 1).trim();
        break;
      }
    }
  }
  return cleanedWord;
}

function playWithWikimedia(originalWord, wordWithoutArticle, langCode) {
  const encodedWord = encodeURIComponent(wordWithoutArticle);

  // Формируем правильный префикс (de -> De, en -> En, fr -> Fr)
  const prefix =
    langCode.charAt(0).toUpperCase() + langCode.slice(1).toLowerCase();

  // Примечание: Wikimedia чаще всего использует префиксы вида De-Wort.ogg, En-Word.ogg.
  // Если аудио для другого языка там нет (возникнет ошибка 404), наш код автоматически
  // перехватит ошибку через audio.onerror и включит стандартный системный голос.
  const audioUrl = `https://commons.wikimedia.org/wiki/Special:FilePath/${prefix}-${encodedWord}.ogg`;

  const audio = new Audio(audioUrl);
  audio.volume = 1.0;

  audio.onerror = () => {
    fallbackPlayWord(originalWord, langCode);
  };

  const timeout = setTimeout(() => {
    audio.pause();
    audio.currentTime = 0;
    fallbackPlayWord(originalWord, langCode);
  }, 3000);

  audio.onplay = () => clearTimeout(timeout);

  audio.play().catch(() => {
    fallbackPlayWord(originalWord, langCode);
  });
}

function fallbackPlayWord(word, langCode) {
  if ("speechSynthesis" in window) {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(word);

    // Указываем язык
    utterance.lang = langCode;

    // --- ЛОГИКА ЗАМЕДЛЕНИЯ ---
    // Если язык начинается на 'en' (en-US, en-GB и т.д.), ставим 0.8.
    // Для остальных оставляем 1.0 (или тоже можно снизить до 0.9 для четкости)
    if (langCode.toLowerCase().startsWith("en")) {
      utterance.rate = 0.7;
    } else {
      utterance.rate = 1.0;
    }
    // -------------------------

    utterance.pitch = 1.0;
    utterance.volume = 1.0;

    const voices = window.speechSynthesis.getVoices();

    const targetVoice = voices.find(
      (v) =>
        v.lang.toLowerCase() === langCode.toLowerCase() ||
        v.lang.toLowerCase().startsWith(langCode.toLowerCase() + "-"),
    );

    if (targetVoice) {
      utterance.voice = targetVoice;
    }

    window.speechSynthesis.speak(utterance);
  }
}

// Загружаем голоса при инициализации страницы
if ("speechSynthesis" in window) {
  window.speechSynthesis.getVoices();
  window.speechSynthesis.onvoiceschanged = () => {
    window.speechSynthesis.getVoices();
  };
}
