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
                <?php if ($user_id): ?>
                    <?php if ($is_added): ?>
                        <button class="btn btn-added" disabled>
                            <span>✅ Вже додано</span>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary" onclick="addModuleToDict()">
                            <span>+ Додати у словник</span>
                            <span class="btn-icon">→</span>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/login" class="btn btn-primary">
                        <span>Увійти щоб додати</span>
                        <span class="btn-icon">→</span>
                    </a>
                <?php endif; ?>
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
            Словарь
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
        const moduleId = <?= $id ?>;
        const isAdded = <?= $is_added ? 'true' : 'false' ?>;

        // Функция для показа тостов
        function showToast(message, type = 'success', duration = 4000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <span class="toast-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : '⏳'}</span>
                    <span class="toast-message">${message}</span>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">×</button>
            `;
            
            container.appendChild(toast);
            
            // Анімація входу
            setTimeout(() => toast.classList.add('toast-show'), 10);
            
            // Автоматичне видалення
            setTimeout(() => {
                toast.classList.remove('toast-show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Пошук слів
        document.getElementById('wordSearch').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('.word-item').forEach(item => {
                const german = item.dataset.german;
                const translation = item.dataset.translation;
                
                if (german.includes(query) || translation.includes(query)) {
                    item.style.display = 'grid';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Додавання модуля
        function addModuleToDict() {
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<span>⏳ Додавання...</span>';

            const formData = new FormData();
            formData.append('module_id', moduleId);

            // Отримуємо поточний шлях і будуємо правильний шлях до add_module.php
            const currentPath = window.location.pathname;
            const paths = [
                './add_module.php',
                '../add_module.php',
                'add_module.php',
                window.location.pathname.replace(/module_view\.php/, 'add_module.php')
            ];

            function tryFetch(index) {
                if (index >= paths.length) {
                    btn.disabled = false;
                    btn.innerHTML = '<span>+ Додати у словник</span><span class="btn-icon">→</span>';
                    showToast('❌ Помилка з\'єднання. Спробуйте оновити сторінку.', 'error', 4000);
                    return;
                }

                const path = paths[index];
                
                fetch(path, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-added');
                        btn.innerHTML = '<span>✅ Додано!</span>';
                        btn.disabled = true;
                        
                        setTimeout(() => {
                            btn.innerHTML = `<span>✅ ${data.words_added} слів додано</span>`;
                        }, 500);
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(err => {
                    console.log('Try path ' + path + ':', err.message);
                    tryFetch(index + 1);
                });
            }

            tryFetch(0);
        }
    </script>
</body>
</html>