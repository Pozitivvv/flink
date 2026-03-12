// Получение данных из PHP
const allTranslations = window.phpData.allTranslations;
let words = [];
let totalWords = 0;
let current = 0;
let score = 0;
let mode = "normal";
let answered = false;
let lastActionRemoved = false; // флаг для режима "errors"
let currentAttempts = 2; // Додали лічильник спроб

const q = document.getElementById("question");
const opts = document.getElementById("options");
const prog = document.getElementById("progress");

let selectedDay = window.phpData.selectedDayId;
let selectedMode = "normal";

// ✅ ІНІЦІАЛІЗАЦІЯ РОЗПІЗНАВАННЯ ГОЛОСУ ТА АУДІО
const SpeechRecognition =
  window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = SpeechRecognition ? new SpeechRecognition() : null;

if (recognition) {
  recognition.lang = "de-DE"; // Встановлюємо німецьку мову
  recognition.interimResults = true; // 👈 ТЕПЕР TRUE: Слухаємо "на льоту"
  recognition.maxAlternatives = 1;
}

let audioContext = null;
let analyser = null;
let dataArray = null;
let animationId = null;
let isListening = false;
let mediaStream = null;

// Инициализация при загрузке страницы
document.addEventListener("DOMContentLoaded", function () {
  initializeDayButtons();
  initializeModeButtons();
});

// alerts
function showMessage(message, type = "info", duration = 15000) {
  const container = document.getElementById("notifications");

  const div = document.createElement("div");
  div.className = `message ${
    type === "error" ? "error" : type === "success" ? "success" : ""
  }`;
  div.textContent = message;

  container.appendChild(div);

  setTimeout(() => {
    div.style.opacity = "0";
    div.style.transition = "opacity 0.4s";
    setTimeout(() => div.remove(), 400);
  }, duration);
}

// Установка активной кнопки дня
function initializeDayButtons() {
  if (selectedDay && selectedDay != 0) {
    document.querySelectorAll(".day-btn").forEach((btn) => {
      if (btn.dataset.id == selectedDay) {
        btn.classList.add("active");
      }
    });
  } else {
    const defaultBtn = document.querySelector('.day-btn[data-id="0"]');
    if (defaultBtn) defaultBtn.classList.add("active");
    selectedDay = 0;
  }

  document.querySelectorAll(".day-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      selectedDay = btn.dataset.id;
      document
        .querySelectorAll(".day-btn")
        .forEach((b) => b.classList.remove("active", "selected-day"));
      btn.classList.add("active");
    });
  });
}

// Установка активной кнопки режима
function initializeModeButtons() {
  document.querySelectorAll(".mode-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document
        .querySelectorAll(".mode-btn")
        .forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      selectedMode = btn.dataset.mode;
    });
  });
}

// Начало теста
function startTest() {
  mode = selectedMode;

  fetch("flashcards_loader.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body:
      "day_id=" +
      encodeURIComponent(selectedDay) +
      "&mode=" +
      encodeURIComponent(mode),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.length === 0) {
        showMessage("Немає слів для цього тесту", "error");
        return;
      }

      words = shuffle(data).slice(0, 30);
      totalWords = words.length;
      current = 0;
      score = 0;
      answered = false;
      lastActionRemoved = false;

      hideElement("menu");
      showElement("quizContainer");
      document.querySelector(".controls").style.display = "block";
      showQuestion();
    });
}

// Сброс теста - возврат в меню
function resetTest() {
  hideElement("quizContainer");
  hideElement("resultsContainer");
  showElement("menu");
  if (isListening) stopVisualizer(); // Зупиняємо мікрофон, якщо вийшли
}

function hideElement(id) {
  document.getElementById(id).classList.add("hidden");
}

function showElement(id) {
  document.getElementById(id).classList.remove("hidden");
}

function getOptionsForIndex(correctIndex) {
  if (mode === "articles") {
    return ["Der", "Die", "Das", "—"];
  }

  const correct = words[correctIndex].translation;
  const pool = allTranslations.filter((t) => t !== correct);
  shuffle(pool);

  const options = [correct];
  for (let i = 0; i < pool.length && options.length < 4; i++) {
    if (!options.includes(pool[i])) {
      options.push(pool[i]);
    }
  }

  while (options.length < 4) {
    options.push("(нема перекладу)");
  }

  return shuffle(options);
}

function shuffle(arr) {
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}

function recordError(wordId) {
  fetch("record_error.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "word_id=" + wordId,
  });
}

function removeError(wordId) {
  fetch("remove_error.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "word_id=" + wordId,
  });
}

// Показ вопроса
function showQuestion() {
  if (!words || words.length === 0 || current >= words.length) {
    if (isListening) stopVisualizer(); // Вимикаємо мікрофон в кінці тесту
    showResults();
    return;
  }

  answered = false;
  currentAttempts = 2;

  const word = words[current];
  const article = word.article ? word.article + " " : "";

  const displayText = mode === "articles" ? word.german : article + word.german;
  const textToSpeak = article + word.german;

  q.innerHTML = "";

  const textSpan = document.createElement("span");
  textSpan.textContent = displayText;
  q.appendChild(textSpan);

  const audioBtn = document.createElement("button");
  audioBtn.className = "audio-btn";
  audioBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
        </svg>
    `;

  audioBtn.onclick = (e) => {
    e.stopPropagation();
    if (typeof playWord === "function") playWord(textToSpeak);
  };

  q.appendChild(audioBtn);

  // ✅ РЕЖИМ ГОЛОСОВОГО ВВЕДЕННЯ (З МЕМБРАНОЮ)
  if (mode === "voice") {
    if (!recognition) {
      opts.innerHTML = `<p style="color: #ef4444; text-align: center; margin-top: 20px;">Ваш браузер не підтримує розпізнавання голосу.</p>`;
      return;
    }

    // Перевіряємо, чи контейнер мікрофона вже існує
    let voiceContainer = document.querySelector(".voice-input-container");

    if (!voiceContainer) {
      opts.innerHTML = "";
      voiceContainer = document.createElement("div");
      voiceContainer.className = "voice-input-container";
      voiceContainer.innerHTML = `
          <div class="mic-visualizer-wrapper">
              <canvas id="micVisualizer" width="150" height="150"></canvas>
              <button type="button" class="mic-btn-round" id="micBtn">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="28" height="28">
                      <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                      <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                  </svg>
              </button>
          </div>
          <p class="mic-status-text" id="micStatus"></p>
      `;
      opts.appendChild(voiceContainer);

      const micBtn = document.getElementById("micBtn");
      micBtn.onclick = () => {
        if (isListening) stopVisualizer();
        else startVisualizer();
      };
    } else {
      document.getElementById("micBtn").classList.remove("correct", "wrong");
    }

    const micStatus = document.getElementById("micStatus");

    if (isListening) {
      micStatus.textContent = "Слухаю наступне... Говоріть";
      micStatus.style.color = "#3b82f6";
      try {
        recognition.start();
      } catch (e) {}
    } else {
      micStatus.textContent = "Натисніть, щоб сказати";
      micStatus.style.color = "#9ca3af";
    }

    prog.textContent = `Слово ${current + 1} з ${words.length}`;
    return;
  }

  // ✅ ЗВИЧАЙНІ РЕЖИМИ
  opts.innerHTML = "";
  const options = getOptionsForIndex(current);

  options.forEach((opt) => {
    const div = document.createElement("div");
    div.className = "option";
    div.textContent = opt;
    div.onclick = () => selectOption(div, opt);
    opts.appendChild(div);
  });

  prog.textContent = `Слово ${current + 1} з ${words.length}`;
}

// ==========================================
// 🎙️ АУДІО ВІЗУАЛІЗАЦІЯ ТА РОЗПІЗНАВАННЯ
// ==========================================

async function startVisualizer() {
  if (answered) return;
  const micBtn = document.getElementById("micBtn");
  const micStatus = document.getElementById("micStatus");

  micBtn.classList.remove("wrong", "correct");

  try {
    if (!mediaStream) {
      mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
      audioContext = new (window.AudioContext || window.webkitAudioContext)();
      analyser = audioContext.createAnalyser();
      const source = audioContext.createMediaStreamSource(mediaStream);
      source.connect(analyser);

      analyser.fftSize = 256;
      dataArray = new Uint8Array(analyser.frequencyBinCount);
    }

    isListening = true;
    micBtn.classList.add("recording");
    micStatus.textContent = "Слухаю... Говоріть";
    micStatus.style.color = "#3b82f6";

    if (!animationId) drawVisualizer();

    try {
      recognition.start();
    } catch (e) {}

    recognition.onresult = (event) => {
      if (answered) return;

      let transcript = "";
      let isFinal = false;

      for (let i = event.resultIndex; i < event.results.length; ++i) {
        transcript += event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          isFinal = true;
        }
      }

      // Зберігаємо текст глобально для таймера
      window.lastTranscript = transcript;

      // Очищаємо попередній таймер тиші
      if (window.speechTimeout) clearTimeout(window.speechTimeout);

      checkVoiceAnswer(transcript, isFinal);

      // 👈 ТАЙМЕР ТИШІ: Якщо браузер завис і пройшло 2 секунди без нових слів
      if (!isFinal && !answered) {
        window.speechTimeout = setTimeout(() => {
          if (isListening && !answered) {
            checkVoiceAnswer(window.lastTranscript, true); // Форсуємо фінал
          }
        }, 2000);
      }
    };

    recognition.onerror = (event) => {
      if (event.error === "no-speech") return;
      stopVisualizer();
      micStatus.textContent = "Помилка: " + event.error;
      micStatus.style.color = "#ef4444";
    };

    recognition.onend = () => {
      if (isListening && !answered) {
        try {
          recognition.start();
        } catch (e) {}
      }
    };
  } catch (err) {
    console.error("Мікрофон недоступний:", err);
    micStatus.textContent = "Доступ до мікрофона закрито";
    micStatus.style.color = "#ef4444";
  }
}

function stopVisualizer(resetText = true) {
  isListening = false;
  const micBtn = document.getElementById("micBtn");
  const micStatus = document.getElementById("micStatus");
  const micVisualizer = document.getElementById("micVisualizer");

  // Очищаємо таймер тиші при зупинці
  if (window.speechTimeout) {
    clearTimeout(window.speechTimeout);
    window.speechTimeout = null;
  }

  if (micBtn) micBtn.classList.remove("recording");
  if (micStatus && resetText && !answered) {
    if (currentAttempts === 2) {
      micStatus.textContent = "Натисніть, щоб сказати";
      micStatus.style.color = "#9ca3af";
    }
  }

  if (animationId) {
    cancelAnimationFrame(animationId);
    animationId = null;
  }
  if (audioContext && audioContext.state !== "closed") {
    audioContext.close();
    audioContext = null;
  }
  if (mediaStream) {
    mediaStream.getTracks().forEach((track) => track.stop());
    mediaStream = null;
  }

  try {
    recognition.stop();
  } catch (e) {}

  if (micVisualizer) {
    const canvasCtx = micVisualizer.getContext("2d");
    canvasCtx.clearRect(0, 0, micVisualizer.width, micVisualizer.height);
  }
}

function drawVisualizer() {
  const micVisualizer = document.getElementById("micVisualizer");
  if (!isListening || !micVisualizer) return;

  const canvasCtx = micVisualizer.getContext("2d");
  const width = micVisualizer.width;
  const height = micVisualizer.height;
  const centerX = width / 2;
  const centerY = height / 2;

  animationId = requestAnimationFrame(drawVisualizer);

  analyser.getByteFrequencyData(dataArray);

  let sum = 0;
  for (let i = 0; i < dataArray.length; i++) {
    sum += dataArray[i];
  }
  let average = sum / dataArray.length;

  const sensitivity = 3.5;
  let boostedAverage = Math.min(255, average * sensitivity);

  canvasCtx.clearRect(0, 0, width, height);

  const alpha = Math.min(1, Math.max(0.1, boostedAverage / 150));
  canvasCtx.fillStyle = `rgba(59, 130, 246, ${alpha * 0.4})`;
  canvasCtx.strokeStyle = `rgba(59, 130, 246, ${alpha})`;
  canvasCtx.lineWidth = 2;

  const baseRadius = 35;
  const maxExtraRadius = 40;

  const pulseRadius = baseRadius + (boostedAverage / 255) * maxExtraRadius;

  canvasCtx.beginPath();
  canvasCtx.arc(centerX, centerY, pulseRadius, 0, 2 * Math.PI);
  canvasCtx.fill();
  canvasCtx.stroke();
}

// 👈 ТУТ ЗМІНИ: Додали параметр isFinal
function checkVoiceAnswer(transcript, isFinal) {
  if (answered) return; // Запобіжник від подвійних спрацьовувань

  if (window.speechTimeout && isFinal) clearTimeout(window.speechTimeout);

  const word = words[current];
  const micBtn = document.getElementById("micBtn");
  const micStatus = document.getElementById("micStatus");

  let targetWord = word.german.toLowerCase().replace(/[^a-zäöüß]/g, "");
  let spokenWord = transcript.toLowerCase().replace(/[^a-zäöüß]/g, "");

  // 👈 ЛІМІТ БРЕДУ: Якщо наговорив занадто багато зайвого (захист від зависання)
  if (spokenWord.length > targetWord.length + 25) {
    isFinal = true;
  }

  if (spokenWord === targetWord || spokenWord.includes(targetWord)) {
    // ✅ ПРАВИЛЬНО
    answered = true;
    try {
      recognition.stop();
    } catch (e) {}

    micBtn.classList.add("correct");
    micStatus.textContent = "Правильно!";
    micStatus.style.color = "#10b981";
    score++;
    removeError(word.id);

    setTimeout(() => {
      micBtn.classList.remove("correct");
      nextQuestion();
    }, 1500);
  } else if (isFinal) {
    // ❌ НЕВІРНО
    currentAttempts--;
    stopVisualizer(false);

    if (currentAttempts > 0) {
      micBtn.classList.add("wrong");
      micStatus.innerHTML = `❌ Невірно.<br><span style="font-size:13px; color:#9ca3af">Натисніть мікрофон ще раз</span>`;
      micStatus.style.color = "#ef4444";

      setTimeout(() => {
        micBtn.classList.remove("wrong");
      }, 1500);
    } else {
      answered = true;
      micBtn.classList.add("wrong");
      micStatus.innerHTML = `❌ Невірно.<br><span style="font-size:15px; color:#9ca3af">Правильно: <b style="color:#fff">${word.german}</b></span>`;
      micStatus.style.color = "#ef4444";
      recordError(word.id);

      setTimeout(() => {
        micBtn.classList.remove("wrong");
        nextQuestion();
      }, 3500);
    }
  }
}
// ==========================================
// ЛОГІКА ЗВИЧАЙНИХ КВІЗІВ
// ==========================================

function selectOption(div, opt) {
  if (answered) return;
  if (div.classList.contains("wrong")) return;

  const word = words[current];
  const isCorrect =
    mode === "articles"
      ? opt === (word.article || "—")
      : opt === word.translation;

  if (isCorrect) {
    answered = true;
    lastActionRemoved = false;

    const buttons = document.querySelectorAll(".option");
    buttons.forEach((b) => (b.onclick = null));

    div.classList.add("correct");
    score++;
    removeError(word.id);

    if (mode === "errors") {
      words.splice(current, 1);
      lastActionRemoved = true;

      if (words.length === 0) {
        setTimeout(() => showResults(), 500);
        return;
      }
    }
  } else {
    currentAttempts--;
    div.classList.add("wrong");

    if (currentAttempts > 0) {
      div.style.pointerEvents = "none";
    } else {
      answered = true;
      const buttons = document.querySelectorAll(".option");
      buttons.forEach((b) => (b.onclick = null));

      buttons.forEach((b) => {
        if (
          (mode === "articles" && b.textContent === (word.article || "—")) ||
          (mode !== "articles" && b.textContent === word.translation)
        ) {
          b.classList.add("correct");
        }
      });

      recordError(word.id);
    }
  }
}

function nextQuestion() {
  if (!answered) return;

  if (mode === "errors") {
    if (!lastActionRemoved) {
      current++;
    }
    lastActionRemoved = false;

    if (current >= words.length || words.length === 0) {
      showResults();
      return;
    }

    showQuestion();
  } else {
    current++;

    if (current >= words.length) {
      showResults();
      return;
    }

    showQuestion();
  }
}

function showResults() {
  hideElement("quizContainer");
  showElement("resultsContainer");

  const percentage = Math.round((score / totalWords) * 100);

  document.getElementById("finalScore").textContent =
    `${score} з ${totalWords}`;
  document.getElementById("percentageText").textContent =
    `${percentage}% правильних відповідей`;

  const icon = document.querySelector(".results-icon");
  if (percentage === 100) {
    icon.textContent = "🏆";
  } else if (percentage >= 80) {
    icon.textContent = "🎉";
  } else if (percentage >= 60) {
    icon.textContent = "👍";
  } else {
    icon.textContent = "💪";
  }
}
