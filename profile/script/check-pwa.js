// РЕДАГУВАННЯ ПРОФІЛЯ check-pwa.js

function openEditModal() {
  document.getElementById("editModal").classList.add("active");
}

function closeEditModal() {
  document.getElementById("editModal").classList.remove("active");
  document.getElementById("editAlert").classList.remove("show");
}

document.getElementById("editForm").addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch(window.location.href, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      const alertBox = document.getElementById("editAlert");

      if (data.success) {
        alertBox.textContent = "✅ " + data.message;
        alertBox.classList.add("show", "alert-success");
        alertBox.classList.remove("alert-error");
        setTimeout(() => {
          closeEditModal();
          location.reload();
        }, 1500);
      } else {
        alertBox.textContent = "❌ " + data.message;
        alertBox.classList.add("show", "alert-error");
        alertBox.classList.remove("alert-success");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      const alertBox = document.getElementById("editAlert");
      alertBox.textContent = "❌ Помилка з'єднання";
      alertBox.classList.add("show", "alert-error");
      alertBox.classList.remove("alert-success");
    });
});

// ЗМІНА ПАРОЛЯ
function openPasswordModal() {
  document.getElementById("passwordModal").classList.add("active");
}

function closePasswordModal() {
  document.getElementById("passwordModal").classList.remove("active");
  document.getElementById("passwordAlert").classList.remove("show");
  document.getElementById("passwordForm").reset();
}

document
  .getElementById("passwordForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch(window.location.href, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        const alertBox = document.getElementById("passwordAlert");

        if (data.success) {
          alertBox.textContent = "✅ " + data.message;
          alertBox.classList.add("show", "alert-success");
          alertBox.classList.remove("alert-error");
          document.getElementById("passwordForm").reset();
          setTimeout(() => closePasswordModal(), 1500);
        } else {
          alertBox.textContent = "❌ " + data.message;
          alertBox.classList.add("show", "alert-error");
          alertBox.classList.remove("alert-success");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        const alertBox = document.getElementById("passwordAlert");
        alertBox.textContent = "❌ Помилка з'єднання";
        alertBox.classList.add("show", "alert-error");
        alertBox.classList.remove("alert-success");
      });
  });

// ВИХІД
function openLogoutModal() {
  document.getElementById("logoutModal").classList.add("active");
}

function closeLogoutModal() {
  document.getElementById("logoutModal").classList.remove("active");
}

function confirmLogout() {
  document.getElementById("logoutForm").submit();
}

// Закриття модалок при клику поза ними
document.querySelectorAll(".modal-overlay").forEach((modal) => {
  modal.addEventListener("click", function (e) {
    if (e.target === this) {
      this.classList.remove("active");
    }
  });
});

// Закриття модалок при натиску ESC
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    document.querySelectorAll(".modal-overlay.active").forEach((modal) => {
      modal.classList.remove("active");
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const installBtn = document.getElementById("installAppBtn");
  let deferredPrompt;

  // --- 1. Функції перевірки ---
  function isIOS() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
  }

  function isAppInstalled() {
    // Для iOS (standalone mode)
    if (isIOS()) {
      return window.navigator.standalone === true;
    }
    // Для Android/PC (display-mode check)
    return window.matchMedia("(display-mode: standalone)").matches;
  }

  // Якщо додаток вже встановлено - кнопку не показуємо і виходимо
  if (isAppInstalled()) {
    return;
  }

  // --- 2. Логіка для Android / Desktop ---
  window.addEventListener("beforeinstallprompt", (e) => {
    // Запобігаємо автоматичному показу (для Chrome < 68)
    e.preventDefault();
    deferredPrompt = e;

    // Показуємо кнопку в меню
    if (installBtn) {
      installBtn.style.display = "block"; // Або "inline-block" / "flex" залежно від стилів

      // Обробник кліку
      installBtn.addEventListener("click", () => {
        if (deferredPrompt) {
          deferredPrompt.prompt();
          deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === "accepted") {
              console.log("✅ Користувач погодився");
              installBtn.style.display = "none";
            }
            deferredPrompt = null;
          });
        }
      });
    }
  });

  // --- 3. Логіка для iOS ---
  // На iOS немає події beforeinstallprompt.
  // Ми перевіряємо, чи це iOS, і чи це НЕ встановлений додаток.
  if (isIOS() && !isAppInstalled()) {
    if (installBtn) {
      installBtn.style.display = "block";

      installBtn.addEventListener("click", () => {
        showIOSInstallModal();
      });
    }
  }
});

// --- 4. Функція для показу модалки iOS ---
function showIOSInstallModal() {
  // Створюємо HTML модалки динамічно, якщо її немає
  if (!document.getElementById("iosInstallModal")) {
    const modalHTML = `
          <div id="iosInstallModal" class="ios-modal-overlay" onclick="closeIOSModal(event)">
            <div class="ios-modal-content" onclick="event.stopPropagation()">
              <div class="ios-modal-header">
                <h3>Встановити Wortly</h3>
                <button class="ios-close-btn" onclick="closeIOSModal()">&times;</button>
              </div>
              <div class="ios-body">
                <p>Щоб встановити додаток на iPhone:</p>
                
                <div class="ios-step">
                  <div class="step-icon">1️⃣</div>
                  <div class="step-text">Натисніть кнопку <strong>"Поділитися"</strong> <br> <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/iOS_Share_icon.svg/32px-iOS_Share_icon.svg.png" style="vertical-align: middle; width: 20px; margin-top: 2px;"> внизу екрана</div>
                </div>

                <div class="ios-step">
                  <div class="step-icon">2️⃣</div>
                  <div class="step-text">Виберіть <strong>"На екран «Домівка»"</strong> <br> (прокрутіть список вниз)</div>
                </div>

                <div class="ios-step">
                  <div class="step-icon">3️⃣</div>
                  <div class="step-text">Натисніть <strong>"Додати"</strong> у верхньому кутку</div>
                </div>
              </div>
            </div>
          </div>
        `;
    document.body.insertAdjacentHTML("beforeend", modalHTML);
  }

  // Показуємо з анімацією
  const modal = document.getElementById("iosInstallModal");
  modal.style.display = "flex";
  // Невелика затримка для спрацювання CSS transition
  setTimeout(() => {
    modal.classList.add("active");
  }, 10);
}

// Закриття модалки
function closeIOSModal(event) {
  const modal = document.getElementById("iosInstallModal");
  if (modal) {
    modal.classList.remove("active");
    setTimeout(() => {
      modal.style.display = "none";
    }, 300);
  }
}
