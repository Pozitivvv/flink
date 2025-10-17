console.log("🎧 Voice system loaded");

// ---------- Озвучка слова ----------
function playWord(word) {
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

  if (isIOS) {
    playWithWikimedia(word);
  } else {
    fallbackPlayWord(word);
  }
}

function playWithWikimedia(word) {
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
  const audioUrl = `https://commons.wikimedia.org/wiki/Special:FilePath/De-${encodedWord}.ogg`;

  const audio = new Audio(audioUrl);
  audio.volume = 1.0;

  const timeout = setTimeout(() => {
    audio.pause();
    audio.currentTime = 0;
    fallbackPlayWord(word);
  }, 3000);

  audio.onplay = () => clearTimeout(timeout);
  audio.onerror = () => fallbackPlayWord(word);

  audio.play().catch(() => fallbackPlayWord(word));
}

function fallbackPlayWord(word) {
  if ("speechSynthesis" in window) {
    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(word);
    utterance.lang = "de-DE";
    utterance.rate = 0.9;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;

    const voices = window.speechSynthesis.getVoices();
    const germanVoice = voices.find(
      (v) => v.lang === "de-DE" || v.lang === "de" || v.lang.startsWith("de-")
    );

    if (germanVoice) utterance.voice = germanVoice;
    setTimeout(() => window.speechSynthesis.speak(utterance), 100);
  }
}

if ("speechSynthesis" in window) {
  window.speechSynthesis.onvoiceschanged = () => {
    window.speechSynthesis.getVoices();
  };
}

// ---------- Подключение кнопок озвучки ----------
function attachSoundEvents() {
  document.querySelectorAll(".sound-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const wordCell = this.closest(".word-cell");
      const word = wordCell.dataset.word;

      this.classList.add("playing");
      setTimeout(() => this.classList.remove("playing"), 300);

      playWord(word);
    });
  });

  document.querySelectorAll(".word-cell").forEach((cell) => {
    cell.style.cursor = "pointer";
    cell.addEventListener("click", function (e) {
      if (e.target.classList.contains("sound-btn")) return;

      const word = this.dataset.word;
      const soundBtn = this.querySelector(".sound-btn");

      if (soundBtn) {
        soundBtn.classList.add("playing");
        setTimeout(() => soundBtn.classList.remove("playing"), 300);
      }

      playWord(word);
    });
  });
}

// ---------- Остальная логика (поиск, фильтр, удаление) ----------
let wordIdToDelete = null;
const modal = document.getElementById("deleteModal");
const cancelBtn = document.getElementById("cancelDelete");
const confirmBtn = document.getElementById("confirmDelete");

function attachDeleteEvents() {
  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      wordIdToDelete = this.dataset.id;
      modal.classList.add("active");
    });
  });
}

cancelBtn.addEventListener("click", () => {
  modal.classList.remove("active");
  wordIdToDelete = null;
});

modal.addEventListener("click", (e) => {
  if (e.target === modal) {
    modal.classList.remove("active");
    wordIdToDelete = null;
  }
});

confirmBtn.addEventListener("click", function () {
  if (wordIdToDelete) {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    formData.append("delete_id", wordIdToDelete);
    xhr.open("POST", "", true);
    xhr.onload = function () {
      if (xhr.responseText.trim() === "success") {
        modal.classList.remove("active");
        wordIdToDelete = null;
        filterWords();
      }
    };
    xhr.send(formData);
  }
});

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape" && modal.classList.contains("active")) {
    modal.classList.remove("active");
    wordIdToDelete = null;
  }
});

function filterWords() {
  const dayId = document.getElementById("daySelect").value;
  const search = document.getElementById("searchInput").value;
  const tbody = document.getElementById("wordsTable");

  tbody.classList.add("loading");
  const xhr = new XMLHttpRequest();
  const params =
    "ajax=1&day_id=" +
    encodeURIComponent(dayId) +
    "&search=" +
    encodeURIComponent(search);

  xhr.open("GET", "?" + params, true);
  xhr.onload = function () {
    if (xhr.status === 200) {
      tbody.innerHTML = xhr.responseText;
      tbody.classList.remove("loading");
      attachDeleteEvents();
      attachSoundEvents();
    }
  };
  xhr.send();
}

document.getElementById("clearBtn").addEventListener("click", function () {
  document.getElementById("searchInput").value = "";
  document.getElementById("daySelect").value = "";
  filterWords();
});

document.getElementById("searchInput").addEventListener("input", filterWords);
document.getElementById("daySelect").addEventListener("change", filterWords);

document.addEventListener("DOMContentLoaded", function () {
  attachDeleteEvents();
  attachSoundEvents();
});
