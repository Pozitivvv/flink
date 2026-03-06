<?php
session_start();
require_once '../../config.php';

$user_id = $_SESSION['user_id'] ?? null;
$id = (int)($_GET['id'] ?? 0);

// Получаем модуль
$stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->execute([$id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$module) die("❌ Модуль не знайдено");

// Получаем слова модуля
$stmt = $pdo->prepare("SELECT * FROM module_words WHERE module_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Проверяем, добавлен ли модуль пользователем
$is_added = false;
if ($user_id) {
    $checkStmt = $pdo->prepare("SELECT id FROM user_modules WHERE user_id = ? AND module_id = ?");
    $checkStmt->execute([$user_id, $id]);
    $is_added = (bool)$checkStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($module['title']) ?></title>
    <link rel="stylesheet" href="assets/module_view.css">
    <link rel="stylesheet" href="../../assets/main-style.css">
</head>
<body>
    <!-- УВЕДОМЛЕНИЯ -->
    <div id="toastContainer" class="toast-container"></div>

    <div class="container">
        <!-- ЗАГОЛОВОК -->
        <div class="page-header">
            <a href="javascript:history.back()" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </a>
            <h1><?= htmlspecialchars($module['title']) ?></h1>
        </div>

        <!-- ЗОБРАЖЕННЯ ТА ОПИС -->
        <div class="module-hero">
            <div class="module-image">
                <img src="<?= !empty($module['image']) ? htmlspecialchars($module['image']) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 300%22%3E%3Cdefs%3E%3ClinearGradient id=%22grad%22 x1=%220%25%22 y1=%220%25%22 x2=%22100%25%22 y2=%22100%25%22%3E%3Cstop offset=%220%25%22 style=%22stop-color:%233b82f6;stop-opacity:1%22 /%3E%3Cstop offset=%22100%25%22 style=%22stop-color:%232563eb;stop-opacity:1%22 /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22400%22 height=%22300%22 fill=%22url(%23grad)%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2264%22 fill=%22white%22 font-family=%22system-ui%22 font-weight=%22bold%22%3E📚%3C/text%3E%3C/svg%3E' ?>" 
                     alt="<?= htmlspecialchars($module['title']) ?>" class="module-img">
            </div>
            <div class="module-info">
                <p class="module-description"><?= htmlspecialchars($module['description']) ?></p>
                <div class="module-stats">
                    <div class="stat-item">
                        <span class="stat-icon">📖</span>
                        <span class="stat-label"><?= count($words) ?> слів</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-icon">⏱️</span>
                        <span class="stat-label">15-20 хв</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-icon">🎯</span>
                        <span class="stat-label">Початківець</span>
                    </div>
                </div>

                <!-- КНОПКА ДОДАВАННЯ -->
                <div class="module-actions">
                    <?php if ($user_id): ?>
                        <?php if ($is_added): ?>
                            <button class="btn btn-added" disabled>
                                <span>✅ Вже додано</span>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-primary" onclick="addModuleToDict()">
                                <span>Додати у словник</span>
                                <span class="btn-icon">→</span>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="../../" class="btn btn-primary">
                            <span>Увійти щоб додати</span>
                            <span class="btn-icon">→</span>
                        </a>
                    <?php endif; ?>

                    <button class="btn btn-secondary" onclick="openShareModal()">
                        <span>Поділитися</span>
                        <span class="btn-icon">🔗</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ТАБЛИЦЯ СЛІВ -->
        <div class="words-section">
            <h2 class="section-title">📚 Слова модуля</h2>
            
            <!-- ПОШУК -->
            <div class="search-box">
                <input type="text" id="wordSearch" placeholder="🔍 Пошук слова..." class="search-input">
            </div>

            <!-- ТАБЛИЦЯ -->
            <div class="words-table">
                <div class="table-header">
                    <div class="col col-article">Артикль</div>
                    <div class="col col-german">Слово</div>
                    <div class="col col-translation">Переклад</div>
                    <div class="col col-type">Тип</div>
                </div>

                <?php foreach ($words as $w): ?>
                    <div class="table-row word-item" data-german="<?= htmlspecialchars(strtolower($w['german'])) ?>" data-translation="<?= htmlspecialchars(strtolower($w['translation'])) ?>">
                        <div class="col col-article">
                            <span class="article"><?= htmlspecialchars($w['article'] ?? '-') ?></span>
                        </div>
                        <div class="col col-german">
                            <span class="german"><?= htmlspecialchars($w['german']) ?></span>
                        </div>
                        <div class="col col-translation">
                            <span class="translation"><?= htmlspecialchars($w['translation']) ?></span>
                        </div>
                        <div class="col col-type">
                            <span class="type-badge"><?= htmlspecialchars($w['type'] ?? 'word') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- НИЖНЯ НАВІГАЦІЯ -->
    <nav class="bottom-nav">
        <a href="../../dashboard.php" class="nav-item">
            <span>🏠</span>
            Головна
        </a>
        <a href="../../add_day.php" class="nav-item">
            <span>📘</span>
            Теми
        </a>
        <a href="../../dictionary.php" class="nav-item">
            <span>📚</span>
            Словник
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
    <div class="share-overlay" id="shareOverlay" onclick="closeShareModal(event)">
        <div class="share-modal" id="shareModal" onclick="event.stopPropagation()">
            <div class="share-header">
                <h3>Поділитися модулем</h3>
                <button class="share-close" onclick="closeShareModal()">✕</button>
            </div>
            
            <div class="share-networks">
                <a href="#" class="share-network tg" onclick="shareTo('telegram', event)">
                    <span class="network-icon"><img src="../../assets/icon/telegram.svg" alt=""></span>
                    <span class="network-name">Telegram</span>
                </a>
                <a href="#" class="share-network wa" onclick="shareTo('whatsapp', event)">
                    <span class="network-icon"><img src="../../assets/icon/whatsapp.svg" alt=""></span>
                    <span class="network-name">WhatsApp</span>
                </a>
                <a href="#" class="share-network fb" onclick="shareTo('facebook', event)">
                    <span class="network-icon"><img src="../../assets/icon/facebook.svg" alt=""></span>
                    <span class="network-name">Facebook</span>
                </a>
            </div>

            <div class="share-copy-box">
                <input type="text" id="shareLinkInput" readonly value="">
                <button class="btn-copy" onclick="copyShareLink()">Копіювати</button>
            </div>
        </div>
    </div>
    <script>
        // Передаємо дані з PHP в зовнішній JavaScript файл
        window.MODULE_CONFIG = {
            id: <?= $id ?>,
            isAdded: <?= $is_added ? 'true' : 'false' ?>
        };
    </script>
    <script src="script/moduleView.js"></script>
</body>
</html>