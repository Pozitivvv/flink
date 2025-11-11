<?php
//modules/index.php

session_start();

try {
    require_once '../../config.php';
} catch (Exception $e) {
    die('Ошибка подключения БД: ' . $e->getMessage());
}

// Получаем ID пользователя
$user_id = $_SESSION['user_id'] ?? null;

// Если не авторизован, перенаправляем
if (!$user_id) {
    header('Location: ../../index.php');
    exit;
}

// Получаем список всех модулей
$query = "SELECT m.id, m.title, m.description, m.image, COUNT(w.id) AS words_count,
          CASE WHEN um.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_added
          FROM modules m
          LEFT JOIN module_words w ON m.id = w.module_id
          LEFT JOIN user_modules um ON m.id = um.module_id AND um.user_id = ?
          GROUP BY m.id
          ORDER BY is_added DESC, m.created_at DESC";

$stmt = $pdo->prepare($query);
if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $pdo->errorInfo()[2]);
}

$result = $stmt->execute([$user_id]);
if (!$result) {
    die('Ошибка выполнения запроса: ' . $pdo->errorInfo()[2]);
}

$all_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// По умолчанию показываем только доступные
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'available';

// Разделяем модули
$added_modules = array();
$available_modules = array();

foreach ($all_modules as $m) {
    if ($m['is_added']) {
        $added_modules[] = $m;
    } else {
        $available_modules[] = $m;
    }
}

// Функция для определения уровня сложности
function getLevel($words_count) {
    if ($words_count <= 20) {
        return array('label' => 'Початківець', 'icon' => '🟢');
    }
    if ($words_count <= 50) {
        return array('label' => 'Середній', 'icon' => '🟡');
    }
    return array('label' => 'Продвинутий', 'icon' => '🔴');
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Маркет модулів для вивчення німецької мови">
  <title>Маркет модулів</title>
  <link rel="stylesheet" href="../../assets/main-style.css">
  <link rel="stylesheet" href="assets/modules.css">
</head>
<body>

  <div class="container">
    <div class="page-header">
        <a href="#" class="back-btn" onclick="goBack()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="square" stroke-linejoin="miter">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </a>
        <h1>📚 Модулі</h1>
    </div>

    <!-- ПОШУК -->
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="🔍 Пошук модулів..." class="search-input">
    </div>

    <!-- ФІЛЬТР -->
    <div class="filter-bar">
      <a href="?filter=available" class="filter-btn <?php echo ($filter === 'available') ? 'filter-btn--active' : ''; ?>">
        <span class="filter-icon">📖</span>
        <span class="filter-label">Доступні</span>
      </a>
      <a href="?filter=added" class="filter-btn <?php echo ($filter === 'added') ? 'filter-btn--active' : ''; ?>">
        <span class="filter-icon">✅</span>
        <span class="filter-label">Мої модулі</span>
      </a>
      <a href="?filter=all" class="filter-btn <?php echo ($filter === 'all') ? 'filter-btn--active' : ''; ?>">
        <span class="filter-icon">🔍</span>
        <span class="filter-label">Усі</span>
      </a>
    </div>

    <!-- ДОСТУПНІ МОДУЛІ -->
    <?php if ((in_array($filter, array('available', 'all')) && !empty($available_modules))): ?>
    <div class="section">
      <h2 class="section-title">📖 Доступні модулі (<?php echo count($available_modules); ?>)</h2>
      <div class="modules-grid">
        <?php foreach ($available_modules as $m): 
          $level = getLevel($m['words_count']);
          $uniqueId = 'grad-' . $m['id'] . '-available';
        ?>
          <div class="module-card module-card--available" data-title="<?php echo htmlspecialchars(strtolower($m['title'])); ?>" data-id="<?php echo intval($m['id']); ?>" data-filter="available">
            <div class="module-image-wrapper">
              <img src="<?php echo (!empty($m['image']) ? htmlspecialchars($m['image']) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 240%22%3E%3Cdefs%3E%3ClinearGradient id=%22' . urlencode($uniqueId) . '%22 x1=%220%25%22 y1=%220%25%22 x2=%22100%25%22 y2=%22100%25%22%3E%3Cstop offset=%220%25%22 style=%22stop-color:%233b82f6;stop-opacity:1%22 /%3E%3Cstop offset=%22100%25%22 style=%22stop-color:%232563eb;stop-opacity:1%22 /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22400%22 height=%22240%22 fill=%22url(%23' . urlencode($uniqueId) . ')%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2248%22 fill=%22white%22 font-family=%22system-ui%22 font-weight=%22bold%22%3E📚%3C/text%3E%3C/svg%3E'); ?>" 
                   alt="<?php echo htmlspecialchars($m['title']); ?>" class="module-img" loading="lazy">
              <div class="module-overlay">
                <span class="module-tag">📚 <?php echo intval($m['words_count']); ?> слів</span>
              </div>
            </div>

            <div class="module-content">
              <h3 class="module-title"><?php echo htmlspecialchars($m['title']); ?></h3>
              <p class="module-description"><?php echo htmlspecialchars($m['description']); ?></p>

              <div class="module-stats">
                <div class="stat">
                  <span class="stat-icon">📖</span>
                  <span class="stat-text"><?php echo intval($m['words_count']); ?> слів</span>
                </div>
                <div class="stat">
                  <span class="stat-icon"><?php echo $level['icon']; ?></span>
                  <span class="stat-text"><?php echo $level['label']; ?></span>
                </div>
              </div>

              <a href="module_view.php?id=<?php echo intval($m['id']); ?>" class="btn btn-primary">
                <span>Переглянути</span>
                <span class="btn-icon">→</span>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ДОДАНІ МОДУЛІ -->
    <?php if ((in_array($filter, array('added', 'all')) && !empty($added_modules))): ?>
    <div class="section">
      <h2 class="section-title">✅ Ваші модулі (<?php echo count($added_modules); ?>)</h2>
      <div class="modules-grid added-modules">
        <?php foreach ($added_modules as $m): 
          $level = getLevel($m['words_count']);
          $uniqueId = 'grad-' . $m['id'] . '-added';
        ?>
          <div class="module-card module-card--added" data-title="<?php echo htmlspecialchars(strtolower($m['title'])); ?>" data-id="<?php echo intval($m['id']); ?>" data-filter="added">
            <div class="module-image-wrapper">
              <img src="<?php echo (!empty($m['image']) ? htmlspecialchars($m['image']) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 240%22%3E%3Cdefs%3E%3ClinearGradient id=%22' . urlencode($uniqueId) . '%22 x1=%220%25%22 y1=%220%25%22 x2=%22100%25%22 y2=%22100%25%22%3E%3Cstop offset=%220%25%22 style=%22stop-color:%2310b981;stop-opacity:1%22 /%3E%3Cstop offset=%22100%25%22 style=%22stop-color:%23059669;stop-opacity:1%22 /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22400%22 height=%22240%22 fill=%22url(%23' . urlencode($uniqueId) . ')%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2248%22 fill=%22white%22 font-family=%22system-ui%22 font-weight=%22bold%22%3E✅%3C/text%3E%3C/svg%3E'); ?>" 
                   alt="<?php echo htmlspecialchars($m['title']); ?>" class="module-img" loading="lazy">
              <div class="module-overlay">
                <span class="module-tag module-tag--added">✅ <?php echo intval($m['words_count']); ?> слів</span>
              </div>
            </div>

            <div class="module-content">
              <h3 class="module-title"><?php echo htmlspecialchars($m['title']); ?></h3>
              <p class="module-description"><?php echo htmlspecialchars($m['description']); ?></p>

              <div class="module-stats">
                <div class="stat">
                  <span class="stat-icon">📖</span>
                  <span class="stat-text"><?php echo intval($m['words_count']); ?> слів</span>
                </div>
                <div class="stat">
                  <span class="stat-icon"><?php echo $level['icon']; ?></span>
                  <span class="stat-text"><?php echo $level['label']; ?></span>
                </div>
              </div>

              <a href="module_view.php?id=<?php echo intval($m['id']); ?>" class="btn btn-success">
                <span>Продовжити</span>
                <span class="btn-icon">→</span>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ПУСТА СТОРІНКА -->
    <?php if (
      ($filter === 'available' && empty($available_modules)) || 
      ($filter === 'added' && empty($added_modules)) ||
      ($filter === 'all' && empty($all_modules))
    ): ?>
      <div class="empty-state">
        <div class="empty-icon">📚</div>
        <h2><?php echo ($filter === 'added') ? 'У вас немає модулів' : 'Немає модулів'; ?></h2>
        <p><?php echo ($filter === 'added') ? 'Додайте модулі щоб почати навчання' : 'Скоро тут з\'являться нові модулі для вивчення'; ?></p>
      </div>
    <?php endif; ?>
  </div>

  <nav class="bottom-nav">
    <a href="../../dashboard.php" class="nav-item">
      <span>🏠</span>
      Головна
    </a>
    <a href="../../add_day.php" class="nav-item">
      <span>📘</span>
      Теми
    </a>
    <a href="../../dictionary.php" class="nav-item active">
      <span>📚</span>
      Модулі
    </a>
    <a href="../../flashcard/practice.php" class="nav-item">
      <span>✏️</span>
      Практика
    </a>
    <a href="../../profile/" class="nav-item">
      <span>👤</span>
      Профіль
    </a>
  </nav>

  <script>
    var searchInput = document.getElementById('searchInput');
    var moduleCards = document.querySelectorAll('.module-card');
    var urlParams = new URLSearchParams(window.location.search);
    var filter = urlParams.get('filter') || 'available';

    if (searchInput) {
      searchInput.addEventListener('input', function(e) {
        var query = this.value.toLowerCase();
        
        for (var i = 0; i < moduleCards.length; i++) {
          var card = moduleCards[i];
          var title = card.getAttribute('data-title') || '';
          var cardFilter = card.getAttribute('data-filter') || '';
          
          var matchesSearch = title.indexOf(query) !== -1;
          var matchesFilter = (filter === 'all' || cardFilter === filter);
          
          if (matchesSearch && matchesFilter) {
            card.style.display = 'flex';
            card.style.animation = 'fadeIn 0.3s ease';
          } else {
            card.style.display = 'none';
          }
        }
      });
    }

    function goBack() {
      history.back();
    }
  </script>
</body>
</html>