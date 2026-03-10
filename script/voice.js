// voice.js
console.log("on voice");

function playWord(word) {
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

  if (isIOS) {
    const wordWithoutArticle = stripArticle(word);

    // Если это связка слов (есть пробел внутри), сразу используем встроенный голос.
    // Это критически важно для iOS, иначе асинхронный фолбэк заблокируется браузером.
    if (wordWithoutArticle.includes(" ")) {
      fallbackPlayWord(word);
    } else {
      // Для одиночных слов пробуем Wikimedia
      playWithWikimedia(word, wordWithoutArticle);
    }
  } else {
    // Для Android/ПК всегда встроенный синтез
    fallbackPlayWord(word);
  }
}

// Вынесли удаление артиклей в отдельную функцию для удобства
function stripArticle(word) {
  const articles = [
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
  ];
  let cleanedWord = word.trim();
  const lowerWord = cleanedWord.toLowerCase();

  for (let article of articles) {
    if (lowerWord.startsWith(article + " ")) {
      cleanedWord = cleanedWord.substring(article.length + 1).trim();
      break;
    }
  }
  return cleanedWord;
}

function playWithWikimedia(originalWord, wordWithoutArticle) {
  const encodedWord = encodeURIComponent(wordWithoutArticle);
  const audioUrl = `https://commons.wikimedia.org/wiki/Special:FilePath/De-${encodedWord}.ogg`;

  const audio = new Audio(audioUrl);
  audio.volume = 1.0;

  audio.onerror = () => {
    fallbackPlayWord(originalWord);
  };

  const timeout = setTimeout(() => {
    audio.pause();
    audio.currentTime = 0;
    fallbackPlayWord(originalWord);
  }, 3000);

  audio.onplay = () => clearTimeout(timeout);

  audio.play().catch(() => {
    fallbackPlayWord(originalWord);
  });
}

function fallbackPlayWord(word) {
  if ("speechSynthesis" in window) {
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(word);
    utterance.lang = "de-DE";
    utterance.rate = 1.0;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;

    const voices = window.speechSynthesis.getVoices();
    const germanVoice = voices.find(
      (v) => v.lang === "de-DE" || v.lang === "de" || v.lang.startsWith("de-"),
    );

    if (germanVoice) {
      utterance.voice = germanVoice;
    }

    // ВАЖНО: убрали setTimeout. На iOS Safari вызов speak() через таймер
    // часто расценивается как программный (без клика) и блокируется.
    window.speechSynthesis.speak(utterance);
  }
}

// Загружаем голоса при инициализации страницы
if ("speechSynthesis" in window) {
  // Safari/iOS иногда требует "пнуть" синтез речи для инициализации голосов
  window.speechSynthesis.getVoices();
  window.speechSynthesis.onvoiceschanged = () => {
    window.speechSynthesis.getVoices();
  };
}
