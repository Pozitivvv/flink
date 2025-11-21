class PWAInstallNotification {
  constructor() {
    this.notification = document.getElementById("pwaNotification");
    this.installBtn = document.getElementById("pwaInstallBtn");
    this.closeBtn = document.getElementById("pwaCloseBtn");

    this.deferredPrompt = null;
    this.touchStartY = 0;
    this.isDragging = false;

    this.init();
  }

  init() {
    // --- БЛОК ИСПРАВЛЕНИЯ ДЛЯ IPHONE (iOS) ---
    // Проверяем, является ли устройство iPhone, iPad или iPod
    const isIOS =
      /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    // Если это iOS — принудительно скрываем и прекращаем работу скрипта
    if (isIOS) {
      if (this.notification) {
        this.notification.style.display = "none";
      }
      console.log("🍎 iOS detected. PWA button hidden (system not supported).");
      return; // Полный выход из функции, дальше код не выполняется
    }
    // -----------------------------------------

    // Перевіряємо чи app вже встановлено (для Android/PC)
    this.checkIfInstalled();

    // Слухаємо beforeinstallprompt
    window.addEventListener("beforeinstallprompt", (e) => {
      e.preventDefault();
      this.deferredPrompt = e;
      this.showNotification();
    });

    // Слухаємо appinstalled
    window.addEventListener("appinstalled", () => {
      this.hideNotification();
      console.log("✅ PWA встановлено");
    });

    // Дія кнопки встановлення
    if (this.installBtn) {
      this.installBtn.addEventListener("click", () => this.install());
    }

    // Дія кнопки закриття
    if (this.closeBtn) {
      this.closeBtn.addEventListener("click", () => this.close());
    }

    // Дії свайпу
    if (this.notification) {
      this.notification.addEventListener("touchstart", (e) =>
        this.handleTouchStart(e)
      );
      this.notification.addEventListener("touchmove", (e) =>
        this.handleTouchMove(e)
      );
      this.notification.addEventListener("touchend", (e) =>
        this.handleTouchEnd(e)
      );
    }
  }

  checkIfInstalled() {
    // Если приложение уже установлено — прячем навсегда
    if (window.matchMedia("(display-mode: standalone)").matches) {
      this.hideNotification();
      return;
    }

    const dismissedTime = localStorage.getItem(
      "pwa-notification-dismissed-time"
    );

    if (dismissedTime) {
      const TWO_DAYS_MS = 1000 * 60 * 60 * 24 * 2; // 172 800 000 мс = 48 часов

      if (Date.now() - parseInt(dismissedTime) < TWO_DAYS_MS) {
        this.hideNotification();
        return;
      }
    }

    // Если прошло ≥ 2 дня и есть событие установки — показываем
    if (this.deferredPrompt) {
      this.showNotification();
    }
  }

  handleTouchStart(e) {
    this.touchStartY = e.touches[0].clientY;
    this.isDragging = true;
    this.notification.style.cursor = "grabbing";
  }

  handleTouchMove(e) {
    if (!this.isDragging) return;

    const touchY = e.touches[0].clientY;
    const diff = touchY - this.touchStartY;

    // Дозволяємо тільки свайп вверх (від'ємне значення)
    if (diff < 0) {
      this.notification.style.transform = `translateY(${diff}px)`;
    }
  }

  handleTouchEnd(e) {
    if (!this.isDragging) return;

    const touchEndY = e.changedTouches[0].clientY;
    const diff = touchEndY - this.touchStartY;

    // Якщо свайп більше 50px вверх - закриваємо
    if (diff < -50) {
      this.close();
    } else {
      // Повертаємо на місце
      this.notification.style.transform = "translateY(0)";
    }

    this.isDragging = false;
    this.notification.style.cursor = "grab";
  }

  showNotification() {
    if (this.notification) {
      this.notification.style.display = "flex";
    }
  }

  hideNotification() {
    if (this.notification) {
      this.notification.style.display = "none";
    }
  }

  async install() {
    if (!this.deferredPrompt) return;

    this.deferredPrompt.prompt();
    const result = await this.deferredPrompt.userChoice;

    if (result.outcome === "accepted") {
      console.log("✅ Користувач прийняв встановлення");
    } else {
      console.log("❌ Користувач відхилив встановлення");
    }

    this.deferredPrompt = null;
    this.close();
  }

  close() {
    if (!this.notification) return;

    this.notification.classList.add("hiding");

    setTimeout(() => {
      this.hideNotification();
      this.notification.classList.remove("hiding");
      this.notification.style.transform = "translateY(0)";
    }, 300);

    // Зберігаємо що користувач закрив
    localStorage.setItem("pwa-notification-dismissed", "true");
    localStorage.setItem(
      "pwa-notification-dismissed-time",
      Date.now().toString()
    );
  }
}

// Ініціалізуємо після завантаження
document.addEventListener("DOMContentLoaded", () => {
  new PWAInstallNotification();
});
