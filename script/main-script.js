// Логіка для кастомних селектів (можна додати в dictionary.js)
document.querySelectorAll(".custom-select-wrapper").forEach((wrapper) => {
  const trigger = wrapper.querySelector(".custom-select-trigger");
  const options = wrapper.querySelectorAll(".custom-option");
  const hiddenInput = wrapper.querySelector('input[type="hidden"]');
  const textSpan = trigger.querySelector("span");

  trigger.addEventListener("click", function (e) {
    document.querySelectorAll(".custom-select-wrapper").forEach((other) => {
      if (other !== wrapper) other.classList.remove("open");
    });
    wrapper.classList.toggle("open");
    e.stopPropagation();
  });

  options.forEach((option) => {
    option.addEventListener("click", function (e) {
      textSpan.textContent = this.textContent.trim();
      hiddenInput.value = this.getAttribute("data-value");

      options.forEach((opt) => opt.classList.remove("selected"));
      this.classList.add("selected");

      wrapper.classList.remove("open");

      // ВАЖЛИВО! Симулюємо подію change для прихованого інпута
      // Це запустить ваш AJAX пошук/фільтр у dictionary.js
      hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));

      e.stopPropagation();
    });
  });
});

document.addEventListener("click", function () {
  document.querySelectorAll(".custom-select-wrapper").forEach((wrapper) => {
    wrapper.classList.remove("open");
  });
});
