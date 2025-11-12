<?php
// admin/dashboard.php


session_start();


require_once __DIR__ . '/../config.php';  // ✅ Правильний шлях
require_once __DIR__ . '/config.php';  

check_admin();

$page = $_GET['page'] ?? 'dashboard';
$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| ✅ СОЗДАНИЕ МОДУЛЯ
|--------------------------------------------------------------------------
*/
if ($page === 'add_module') {
    
    // Обробка POST запиту
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $title = trim($_POST['title']);
            $description = trim($_POST['description'] ?? '');
            $image_url = trim($_POST['image_url'] ?? '');
            $image = '';

            // Пріоритет: спочатку перевіряємо завантажений файл
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $image = upload_image($_FILES['image_file']);
            } 
            // Якщо файл не завантажено, використовуємо URL
            elseif (!empty($image_url)) {
                $image = $image_url;
            }

            $stmt = $pdo->prepare("INSERT INTO modules (title, description, image) VALUES (?, ?, ?)");
            $stmt->execute([$title, $description, $image]);

            header("Location: ?page=modules&created=1");
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    
    // Показ форми
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>➕ Додати модуль</title>
    <link rel="stylesheet" href="assets/main-style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="?page=modules" class="back-btn">←</a>
            <div class="header-content">
                <h1>Додати модуль</h1>
                <p>Створіть новий навчальний модуль</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form class="form-card" method="POST" enctype="multipart/form-data" id="moduleForm">
            
            <div class="form-group">
                <label class="form-label">
                    📚 Назва модуля
                    <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    class="form-input" 
                    placeholder="Наприклад: Базова лексика А1"
                    required
                    maxlength="100"
                    autofocus
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label class="form-label">
                    📝 Опис
                    <span class="optional">(необов'язково)</span>
                </label>
                <textarea 
                    name="description" 
                    class="form-textarea" 
                    placeholder="Короткий опис модуля та його змісту..."
                    maxlength="500"
                ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <div class="form-hint">
                    Опишіть, що вивчатимуть користувачі в цьому модулі
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    🖼️ Зображення модуля
                    <span class="optional">(необов'язково)</span>
                </label>
                
                <div class="upload-container">
                    <div class="file-upload-wrapper">
                        <input 
                            type="file" 
                            name="image_file" 
                            id="imageFile" 
                            class="file-upload-input"
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <label for="imageFile" class="file-upload-btn" id="uploadBtn">
                            <div class="file-upload-icon">📤</div>
                            <div class="file-upload-text">Натисніть, щоб завантажити</div>
                            <div class="file-upload-subtext">JPG, PNG, WebP • Максимум 5MB</div>
                        </label>
                    </div>

                    <div class="file-preview" id="filePreview">
                        <img src="" alt="Preview" class="file-preview-image" id="previewImage">
                        <div class="file-preview-info">
                            <div class="file-preview-name" id="fileName"></div>
                            <div class="file-preview-size" id="fileSize"></div>
                        </div>
                        <button type="button" class="file-remove-btn" id="removeFile">✕</button>
                    </div>
                </div>

                <div class="divider">
                    <span>або</span>
                </div>

                <input 
                    type="url" 
                    name="image_url" 
                    id="imageUrl"
                    class="form-input" 
                    placeholder="https://example.com/image.jpg"
                    value="<?= htmlspecialchars($_POST['image_url'] ?? '') ?>"
                >
                <div class="form-hint">
                    Вставте посилання на зображення або завантажте файл вище
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    ➕ Створити модуль
                </button>
                <a href="?page=modules" class="btn-cancel">
                    ✕ Скасувати
                </a>
            </div>

        </form>
    </div>

    <script>
        const imageFile = document.getElementById('imageFile');
        const imageUrl = document.getElementById('imageUrl');
        const filePreview = document.getElementById('filePreview');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFile = document.getElementById('removeFile');

        imageFile.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Дозволені тільки JPG, PNG, WebP файли');
                    imageFile.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('Файл занадто великий. Максимум 5MB');
                    imageFile.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImage.src = event.target.result;
                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                    filePreview.classList.add('active');
                    imageUrl.value = '';
                };
                reader.readAsDataURL(file);
            }
        });

        removeFile.addEventListener('click', function() {
            imageFile.value = '';
            filePreview.classList.remove('active');
            previewImage.src = '';
        });

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        imageUrl.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                imageFile.value = '';
                filePreview.classList.remove('active');
            }
        });
    </script>
</body>
</html>

<?php
    exit;
}

/*
|--------------------------------------------------------------------------
| ✅ УДАЛЕНИЕ МОДУЛЯ
|--------------------------------------------------------------------------
*/
if ($page === 'delete_module') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id === 0) die("❌ ID модуля не указан");

    $stmt = $pdo->prepare("SELECT image FROM modules WHERE id = ?");
    $stmt->execute([$id]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$module) {
        die("❌ Модуль не найден <a href='?page=modules'>← Назад</a>");
    }

    $pdo->prepare("DELETE FROM module_words WHERE module_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM user_modules WHERE module_id = ?")->execute([$id]);

    if (!empty($module['image'])) {
        $path = dirname(__DIR__) . $module['image'];
        if (file_exists($path)) unlink($path);
    }

    $pdo->prepare("DELETE FROM modules WHERE id = ?")->execute([$id]);

    header("Location: ?page=modules&deleted=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| ✅ СПИСОК МОДУЛЕЙ
|--------------------------------------------------------------------------
*/
if ($page === 'modules') {
    $stmt = $pdo->query("
        SELECT m.id, m.title, m.description, m.image, m.created_at, 
               COUNT(DISTINCT um.user_id) as users_count
        FROM modules m
        LEFT JOIN user_modules um ON m.id = um.module_id
        GROUP BY m.id
        ORDER BY m.id DESC
    ");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛡️ Админ-панель</title>
    <link rel="stylesheet" href="assets/main-style.css">
</head>
<body>
    <div class="container">

        <div class="greeting">
            <div class="greeting-content">
                <div class="greeting-icon">🛡️</div>
                <div class="greeting-text">
                    <h1>Админ-панель</h1>
                    <p>Управління модулями</p>
                </div>
            </div>
            <a href="/dashboard.php" class="back-link">
                ← На сайт
            </a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="message">✅ Модуль успішно видалено</div>
        <?php endif; ?>

        <?php if (isset($_GET['created'])): ?>
            <div class="message">✅ Модуль успішно створено</div>
        <?php endif; ?>

        <div class="control-panel">
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input 
                    type="search" 
                    id="searchInput" 
                    class="search-input" 
                    placeholder="Пошук модулів..."
                    autocomplete="off"
                >
                <button class="clear-search" id="clearSearch">✕</button>
            </div>
            <a href="?page=add_module" class="btn-add-module">
                ➕ Додати модуль
            </a>
        </div>

        <?php if (empty($modules)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <div class="empty-state-text">
                    Модулів ще немає.<br>
                    Додайте перший модуль для початку роботи.
                </div>
            </div>
        <?php else: ?>
            <div class="modules-grid" id="modulesGrid">
                <?php foreach ($modules as $m): ?>
                <?php 
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM module_words WHERE module_id = ?");
                    $stmt->execute([$m['id']]);
                    $words_count = $stmt->fetchColumn();
                ?>
                <div class="module-card" data-title="<?= htmlspecialchars(mb_strtolower($m['title'])) ?>">
                    <div class="module-header">
                        <?php if ($m['image']): ?>
                            <img src="<?= htmlspecialchars($m['image']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" class="module-image">
                        <?php else: ?>
                            <div class="module-image-placeholder">📚</div>
                        <?php endif; ?>

                        <div class="module-info">
                            <div class="module-title"><?= htmlspecialchars($m['title']) ?></div>
                            <div class="module-stats">
                                <div class="stat-item">
                                    <span class="stat-icon">📝</span>
                                    <span class="stat-value"><?= $words_count ?></span>
                                    <span>слів</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-icon">👥</span>
                                    <span class="stat-value"><?= $m['users_count'] ?></span>
                                    <span>користувачів</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="module-actions">
                        <a href="?page=edit_module&id=<?= $m['id'] ?>" class="btn">
                            ✏️ Редагувати
                        </a>
                        <button class="btn btn-delete" onclick="openDeleteModal(<?= $m['id'] ?>, '<?= htmlspecialchars($m['title'], ENT_QUOTES) ?>')">
                            🗑️ Видалити
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="no-results" id="noResults">
                <div class="no-results-icon">🔍</div>
                <div class="no-results-text">
                    Нічого не знайдено<br>
                    <small>Спробуйте змінити запит</small>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-icon">⚠️</div>
            <h2 class="modal-title">Видалити модуль?</h2>
            <p class="modal-message">
                Ця дія незворотна. Буде видалено всі слова модуля, зв'язки з користувачами та зображення.
            </p>
            <div class="modal-module-name" id="modalModuleName"></div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-confirm" id="confirmDelete">
                    🗑️ Так, видалити
                </button>
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                    ✕ Скасувати
                </button>
            </div>
        </div>
    </div>

    <script>
        let moduleToDelete = null;

        function openDeleteModal(moduleId, moduleName) {
            moduleToDelete = moduleId;
            document.getElementById('modalModuleName').textContent = moduleName;
            document.getElementById('deleteModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.body.style.overflow = '';
            moduleToDelete = null;
        }

        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (moduleToDelete) {
                window.location.href = '?page=delete_module&id=' + moduleToDelete;
            }
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('deleteModal').classList.contains('active')) {
                closeDeleteModal();
            }
        });

        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const modulesGrid = document.getElementById('modulesGrid');
        const noResults = document.getElementById('noResults');
        const moduleCards = document.querySelectorAll('.module-card');

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                if (searchTerm.length > 0) {
                    clearSearch.classList.add('active');
                } else {
                    clearSearch.classList.remove('active');
                }

                let visibleCount = 0;

                moduleCards.forEach(card => {
                    const title = card.getAttribute('data-title');
                    
                    if (title.includes(searchTerm)) {
                        card.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        card.classList.add('hidden');
                    }
                });

                if (visibleCount === 0 && searchTerm.length > 0) {
                    noResults.classList.add('active');
                } else {
                    noResults.classList.remove('active');
                }
            });

            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                clearSearch.classList.remove('active');
                
                moduleCards.forEach(card => {
                    card.classList.remove('hidden');
                });
                
                noResults.classList.remove('active');
                searchInput.focus();
            });
        }
    </script>
</body>
</html>

<?php
    exit;
}

/*
|--------------------------------------------------------------------------
| ✅ РЕДАКТИРОВАНИЕ МОДУЛЯ
|--------------------------------------------------------------------------
*/
if ($page === 'edit_module') {
    include __DIR__ . '/modules_edit.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| ✅ ИМПОРТ JSON
|--------------------------------------------------------------------------
*/
if ($page === 'import') {
    include __DIR__ . '/modules_import.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| ✅ РЕДИРЕКТ НА СПИСОК МОДУЛЕЙ
|--------------------------------------------------------------------------
*/
header("Location: ?page=modules");
exit;
