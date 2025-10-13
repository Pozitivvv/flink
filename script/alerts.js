document.addEventListener("DOMContentLoaded", () => {
  const message = document.querySelector(".message");
  if (message) {
    setTimeout(() => {
      message.style.transition = "opacity 0.5s ease";
      message.style.opacity = "0";
      setTimeout(() => message.remove(), 500); // удалить после плавного исчезновения
    }, 6000); // 6 секунд
  }
});
