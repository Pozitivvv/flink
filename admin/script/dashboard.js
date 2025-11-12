let moduleToDelete = null;

// ============ МОДАЛЬНОЕ ОКНО ============
function openDeleteModal(moduleId, moduleName) {
  moduleToDelete = moduleId;
  document.getElementById("modalModuleName").textContent = moduleName;
  document.getElementById("deleteModal").classList.add("active");
  document.body.style.overflow = "hidden";
}

function closeDeleteModal() {
  document.getElementById("deleteModal").classList.remove("active");
  document.body.style.overflow = "";
  moduleToDelete = null;
}

document.getElementById("confirmDelete").addEventListener("click", function () {
  if (moduleToDelete) {
    window.location.href = "?page=delete_module&id=" + moduleToDelete;
  }
});

document.getElementById("deleteModal").addEventListener("click", function (e) {
  if (e.target === this) {
    closeDeleteModal();
  }
});

document.addEventListener("keydown", function (e) {
  if (
    e.key === "Escape" &&
    document.getElementById("deleteModal").classList.contains("active")
  ) {
    closeDeleteModal();
  }
});

// ============ LIVE SEARCH ============
const searchInput = document.getElementById("searchInput");
const clearSearch = document.getElementById("clearSearch");
const modulesGrid = document.getElementById("modulesGrid");
const noResults = document.getElementById("noResults");
const moduleCards = document.querySelectorAll(".module-card");

if (searchInput) {
  searchInput.addEventListener("input", function (e) {
    const searchTerm = e.target.value.toLowerCase().trim();

    // Показать/скрыть кнопку очистки
    if (searchTerm.length > 0) {
      clearSearch.classList.add("active");
    } else {
      clearSearch.classList.remove("active");
    }

    let visibleCount = 0;

    // Фильтрация модулей
    moduleCards.forEach((card) => {
      const title = card.getAttribute("data-title");

      if (title.includes(searchTerm)) {
        card.classList.remove("hidden");
        visibleCount++;
      } else {
        card.classList.add("hidden");
      }
    });

    // Показать "нет результатов"
    if (visibleCount === 0 && searchTerm.length > 0) {
      noResults.classList.add("active");
    } else {
      noResults.classList.remove("active");
    }
  });

  // Кнопка очистки поиска
  clearSearch.addEventListener("click", function () {
    searchInput.value = "";
    clearSearch.classList.remove("active");

    moduleCards.forEach((card) => {
      card.classList.remove("hidden");
    });

    noResults.classList.remove("active");
    searchInput.focus();
  });
}
