// assets/module_view.js

// Получаем переменные, которые мы передали из PHP
const moduleId = window.MODULE_CONFIG.id;
const isAdded = window.MODULE_CONFIG.isAdded;

const currentUrl = window.location.href;
const moduleTitle = document.title;

// === УВЕДОМЛЕНИЯ (ТОСТЫ) ===
function showToast(message, type = "success", duration = 4000) {
  const container = document.getElementById("toastContainer");
  const toast = document.createElement("div");

  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${type === "success" ? "✅" : type === "error" ? "❌" : "⏳"}</span>
            <span class="toast-message">${message}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;

  container.appendChild(toast);

  // Анімація входу
  setTimeout(() => toast.classList.add("toast-show"), 10);

  // Автоматичне видалення
  setTimeout(() => {
    toast.classList.remove("toast-show");
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// === ПОШУК СЛІВ ===
document.getElementById("wordSearch").addEventListener("input", (e) => {
  const query = e.target.value.toLowerCase();
  document.querySelectorAll(".word-item").forEach((item) => {
    const german = item.dataset.german;
    const translation = item.dataset.translation;

    if (german.includes(query) || translation.includes(query)) {
      item.style.display = "grid";
    } else {
      item.style.display = "none";
    }
  });
});

// === ДОДАВАННЯ МОДУЛЯ ===
function addModuleToDict(event) {
  // Если event не передан, берем глобальный window.event
  const ev = event || window.event;
  const btn = ev.target.closest("button");

  btn.disabled = true;
  btn.innerHTML = "<span>⏳ Додавання...</span>";

  const formData = new FormData();
  formData.append("module_id", moduleId);

  const paths = [
    "./add_module.php",
    "../add_module.php",
    "add_module.php",
    window.location.pathname.replace(/module_view\.php/, "add_module.php"),
  ];

  function tryFetch(index) {
    if (index >= paths.length) {
      btn.disabled = false;
      btn.innerHTML =
        '<span>Додати у словник</span><span class="btn-icon">→</span>';
      showToast(
        "❌ Помилка з'єднання. Спробуйте оновити сторінку.",
        "error",
        4000,
      );
      return;
    }

    const path = paths[index];

    fetch(path, {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) throw new Error("HTTP " + response.status);
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          btn.classList.remove("btn-primary");
          btn.classList.add("btn-added");
          btn.innerHTML = "<span>✅ Додано!</span>";
          btn.disabled = true;

          setTimeout(() => {
            btn.innerHTML = `<span>✅ ${data.words_added} слів додано</span>`;
          }, 500);
        } else {
          throw new Error(data.message);
        }
      })
      .catch((err) => {
        console.log("Try path " + path + ":", err.message);
        tryFetch(index + 1);
      });
  }

  tryFetch(0);
}

// === ЛОГІКА ДЛЯ КНОПКИ "ПОДІЛИТИСЯ" ===
function openShareModal() {
  document.getElementById("shareLinkInput").value = currentUrl;
  document.getElementById("shareOverlay").classList.add("active");
  document.body.style.overflow = "hidden"; // Блокируем скролл фона
}

function closeShareModal(event) {
  if (
    !event ||
    event.target.id === "shareOverlay" ||
    event.currentTarget.classList.contains("share-close")
  ) {
    document.getElementById("shareOverlay").classList.remove("active");
    document.body.style.overflow = ""; // Возвращаем скролл
  }
}

function shareTo(network, event) {
  event.preventDefault();
  let url = "";
  const encodedUrl = encodeURIComponent(currentUrl);
  const encodedTitle = encodeURIComponent(
    "Я поділився модулем слів. Спробуй вивчити його разом зі мною: " +
      moduleTitle,
  );

  switch (network) {
    case "telegram":
      url = `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`;
      break;
    case "whatsapp":
      url = `https://api.whatsapp.com/send?text=${encodedTitle} ${encodedUrl}`;
      break;
    case "facebook":
      url = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
      break;
  }

  if (url) window.open(url, "_blank", "width=600,height=400");
}

function copyShareLink() {
  navigator.clipboard
    .writeText(currentUrl)
    .then(() => {
      showToast("Посилання скопійовано!", "success", 3000);

      // Добавляем вибрацию для телефона при успешном копировании
      if (navigator.vibrate) {
        navigator.vibrate(50);
      }

      closeShareModal();
    })
    .catch(() => {
      const input = document.getElementById("shareLinkInput");
      input.select();
      document.execCommand("copy");
      showToast("Посилання скопійовано!", "success", 3000);
      closeShareModal();
    });
}
