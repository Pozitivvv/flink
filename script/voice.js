console.log("on voice");

function playWord(word) {
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

  if (isIOS) {
    // Для iOS: сначала пробуем Wikimedia, потом встроенный
    playWithWikimedia(word);
  } else {
    // Для Android: встроенный синтез
    fallbackPlayWord(word);
  }
}

function playWithWikimedia(word) {
  // Отделяем артикль от слова (der, die, das, den, dem, des, etc.)
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
    "einen",
    "eines",
    "einer",
  ];
  let wordWithoutArticle = word;

  for (let article of articles) {
    if (word.toLowerCase().startsWith(article + " ")) {
      wordWithoutArticle = word.substring(article.length + 1);
      break;
    }
  }

  const encodedWord = encodeURIComponent(wordWithoutArticle);
  // Пытаемся найти озвучку в Wikimedia Commons
  const audioUrl = `https://commons.wikimedia.org/wiki/Special:FilePath/De-${encodedWord}.ogg`;

  const audio = new Audio(audioUrl);
  audio.volume = 1.0;

  // Если файл не найден или ошибка - переходим на встроенный синтез
  audio.onerror = () => {
    fallbackPlayWord(word);
  };

  // Таймаут на случай зависания
  const timeout = setTimeout(() => {
    audio.pause();
    audio.currentTime = 0;
    fallbackPlayWord(word);
  }, 3000);

  audio.onplay = () => clearTimeout(timeout);
  audio.play().catch(() => {
    fallbackPlayWord(word);
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
      (v) => v.lang === "de-DE" || v.lang === "de" || v.lang.startsWith("de-")
    );

    if (germanVoice) {
      utterance.voice = germanVoice;
    }

    setTimeout(() => window.speechSynthesis.speak(utterance), 100);
  }
}

// Загружаем голоса при загрузке
if ("speechSynthesis" in window) {
  window.speechSynthesis.onvoiceschanged = () => {
    window.speechSynthesis.getVoices();
  };
}
