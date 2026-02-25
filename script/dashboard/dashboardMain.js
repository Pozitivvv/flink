if ("serviceWorker" in navigator) {
  navigator.serviceWorker
    .register("/sw.js")
    .then((reg) => console.log("✅ Service Worker зарегистрирован:", reg.scope))
    .catch((err) => console.log("❌ SW error:", err));
}
// Эффект таяния приветствия при скролле
window.addEventListener("scroll", () => {
  const greeting = document.getElementById("parallaxGreeting");
  if (greeting) {
    // Получаем позицию скролла
    let scrollY = window.scrollY;
    // При скролле на 100px вниз прозрачность упадет до 0
    let opacity = Math.max(0, 1 - scrollY / 100);
    // Сдвигаем приветствие чуть медленнее, чем основной контент (создает глубину)
    let transformY = scrollY * 0.3;

    greeting.style.opacity = opacity;
    greeting.style.transform = `translateY(${transformY}px)`;
  }
});

// Додавання до словника
function toggleFavorite(wordId, btn) {
  const isActive = btn.classList.contains("active");
  if (isActive) return;

  btn.disabled = true;
  btn.innerHTML = "⏳ Додаємо...";

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "function/add_base_word.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onload = function () {
    if (xhr.status === 200) {
      const response = xhr.responseText.trim();

      if (response === "success" || response === "exists") {
        btn.classList.add("active");
        btn.innerHTML = "❤️ У словнику";
        btn.disabled = false;

        const totalWordsElem = document.querySelector(".stat-card .stat-value");
        if (totalWordsElem) {
          let count = parseInt(totalWordsElem.textContent) || 0;
          totalWordsElem.textContent = count + 1;
        }
      } else {
        btn.innerHTML = "🤍 Додати";
        btn.disabled = false;
      }
    } else {
      btn.innerHTML = "🤍 Додати";
      btn.disabled = false;
    }
  };
  xhr.onerror = function () {
    btn.innerHTML = "🤍 Додати";
    btn.disabled = false;
  };
  xhr.send("word_id=" + wordId);
}
