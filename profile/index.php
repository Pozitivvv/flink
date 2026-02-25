<?php
/**
 * profile.php — сторінка профіля користувача
 */
session_start();
require_once '../config.php';

// Перевірка авторизації
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Обробка AJAX запитів
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

        // Обробка редагування профіля
        if ($_POST['action'] === 'edit_profile') {
            $new_name = trim($_POST['name'] ?? '');
            $new_email = trim($_POST['email'] ?? '');

            if (empty($new_name) || empty($new_email)) {
                $response = ['success' => false, 'message' => 'Заповніть всі поля!'];
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ? 
                    WHERE id = ?
                ");
                if ($stmt->execute([$new_name, $new_email, $user_id])) {
                    $response = ['success' => true, 'message' => 'Профіль успішно оновлено!'];
                } else {
                    $response = ['success' => false, 'message' => 'Помилка при оновленні профіля'];
                }
            }

            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

        // Обробка зміни пароля
        if ($_POST['action'] === 'change_password') {
            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                $response = ['success' => false, 'message' => 'Заповніть всі поля!'];
            } elseif ($new_password !== $confirm_password) {
                $response = ['success' => false, 'message' => 'Новий пароль не збігається з підтвердженням!'];
            } elseif (strlen($new_password) < 6) {
                $response = ['success' => false, 'message' => 'Пароль має бути мінімум 6 символів!'];
            } else {
                // Перевіримо старий пароль
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $stored_password = $stmt->fetchColumn();

                if (!password_verify($old_password, $stored_password)) {
                    $response = ['success' => false, 'message' => 'Старий пароль невірний!'];
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        $response = ['success' => true, 'message' => 'Пароль успішно змінено!'];
                    } else {
                        $response = ['success' => false, 'message' => 'Помилка при зміні пароля'];
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    // Отримуємо дані користувача
    $stmt = $pdo->prepare("
        SELECT id, name, login, email, created_at, is_admin
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    // Підрахунок всіх слів користувача
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_words 
        FROM words 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $words_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_words'];

    // Підрахунок днів/уроків
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_days 
        FROM days 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $days_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_days'];

    // Підрахунок помилок
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_errors 
        FROM user_errors 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $errors_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_errors'];

    // Розрахунок відсотка правильних відповідей
    $correct_words = $words_count - $errors_count;
    $progress = $words_count > 0 ? round(($correct_words / $words_count) * 100) : 0;

    // Форматування дати реєстрації (на німецькій)
    $created_date = new DateTime($user['created_at']);
    $fmt = new IntlDateFormatter(
        'de_DE',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE
    );
    $formatted_date = $fmt->format($created_date);

} catch (PDOException $e) {
    die("❌ Помилка бази даних: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../function/tags/icons.html'; ?>
    <?php include '../function/tags/seo.html'; ?>
    <title>Профіль | Wortly DE</title>
    <link rel="stylesheet" href="style/profile.css">
    <link rel="stylesheet" href="../../assets/main-style.css">
    
</head>
<body>
    <div class="container">
        <!-- Приветствие -->
        <div class="greeting">
            <div class="greeting-icon">👤</div>
            <div class="greeting-text">
                <h1>Hallo, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                <p>Управління вашим профілем і статистикою</p>
            </div>
        </div>

        <!-- Повідомлення про успішний вихід (якщо є) -->
        <?php if (isset($_GET['logout_success'])): ?>
            <div class="alert alert-success">
                ✅ Ви успішно вийшли з акаунту.
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $words_count; ?></div>
                <div class="stat-label">Слів додано</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $days_count; ?></div>
                <div class="stat-label">Днів навчання</div>
            </div>
        </div>

        <!-- Інформація користувача -->
        <div class="widget">
            <div class="widget-title">
                <span class="widget-icon">ℹ️</span>
                Інформація про користувача
            </div>
            <div class="profile-info">
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Datum der Registrierung:</span>
                    <span class="info-value"><?php echo $formatted_date; ?></span>
                </div>
            </div>
        </div>

        <!-- Дії -->
        <div class="widget">
            <div class="widget-title">
                <span class="widget-icon">⚙️</span>
                Дії
            </div>
            <div class="buttons">
                <div class="buttons function-buttons-grid">
                    <a href="../function/achievements/" class="btn btn-function function-overview">
                        <span class="function-icon">📋</span>
                        <div class="function-content">
                            <span class="function-title">Досягнення</span>
                            <span class="function-description">Порахуємо ваші досягнення.</span>
                        </div>
                    </a>
                    <a href="../add_word.php" class="btn btn-function function-words">
                        <span class="function-icon">📚</span>
                        <div class="function-content">
                            <span class="function-title">Додати слова</span>
                            <span class="function-description">Додаємо слова до словника.</span>
                        </div>
                    </a>
                    <a href="../function/interactive/" class="btn btn-function function-flashcards">
                        <span class="function-icon">🎴</span>
                        <div class="function-content">
                            <span class="function-title">Флешкарти</span>
                            <span class="function-description">Вивчаємо слова за допомогою флешкарт.</span>
                        </div>
                    </a>
                    <a href="../flashcard/practice.php" class="btn btn-function function-test">
                        <span class="function-icon">🧠</span>
                        <div class="function-content">
                            <span class="function-title">Тест</span>
                            <span class="function-description">Вивчаємо слова за допомогою тесту.</span>
                        </div>
                    </a>
                    <a href="../function/modules/modules.php" class="btn btn-function function-quiz">
                        <span class="function-icon">🚀</span>
                        <div class="function-content">
                            <span class="function-title">Готові модулі слів</span>
                            <span class="function-description">Вивчаємо слова за допомогою готових модулів.</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php if (!empty($user['is_admin']) && $user['is_admin'] == 1): ?>
                <div class="widget">
                    <div class="widget-title">
                        <span class="widget-icon">🛠️</span>
                        Адмін панель
                    </div>
                    <div class="button-group">
                        <a href="../admin/dashboard.php" class="btn btn-primary" style="grid-column: 1 / -1;">Перейти в адмінку</a>
                    </div>
                </div>
            <?php endif; ?>
            <div class="button-group">
                <button onclick="openEditModal()" class="btn btn-secondary">✏️ Редагувати профіль</button>
                <button onclick="openPasswordModal()" class="btn btn-secondary">🔐 Змінити пароль</button>
                <button onclick="openLogoutModal()" class="btn btn-danger" style="grid-column: 1 / -1;">Ausloggen</button>
            </div>
        </div>
    </div>

    <!-- Прихована форма для виходу -->
    <form id="logoutForm" method="POST" action="logout.php" style="display: none;"></form>

    <nav class="bottom-nav">
        <a href="../dashboard.php" class="nav-item">
            <span>🏠</span>
            Головна
        </a>
        <a href="../add_day.php" class="nav-item">
            <span>📘</span>
            Теми
        </a>
        <a href="../dictionary.php" class="nav-item">
            <span>📚</span>
            Словарь
        </a>
        <a href="../flashcard/practice.php" class="nav-item">
            <span>✏️</span>
            Практика
        </a>
        <a href="#" class="nav-item active">
            <span>👤</span>
            Профіль
        </a>
    </nav>

    <!-- МОДАЛКА РЕДАГУВАННЯ ПРОФІЛЯ -->
    <div id="editModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">✏️</div>
                <h2>Редагувати профіль</h2>
            </div>
            <div id="editAlert" class="alert-box"></div>
            <form id="editForm" class="modal-form">
                <input type="hidden" name="action" value="edit_profile">
                <div class="form-group">
                    <label for="name">Ім'я:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeEditModal()">Скасувати</button>
                    <button type="submit" class="modal-btn modal-btn-submit">Зберегти</button>
                </div>
            </form>
        </div>
    </div>

    <!-- МОДАЛКА ЗМІНИ ПАРОЛЯ -->
    <div id="passwordModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">🔐</div>
                <h2>Змінити пароль</h2>
            </div>
            <div id="passwordAlert" class="alert-box"></div>
            <form id="passwordForm" class="modal-form">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label for="old_password">Старий пароль:</label>
                    <input type="password" id="old_password" name="old_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Новий пароль:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Підтвердіть пароль:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closePasswordModal()">Скасувати</button>
                    <button type="submit" class="modal-btn modal-btn-submit">Змінити</button>
                </div>
            </form>
        </div>
    </div>

    <!-- МОДАЛКА ВИХОДУ -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal">
            <h2 style="margin-bottom: 10px;" class="modal-title">Вихід з акаунту? 😟</h2>
            <p>Ви впевнені, що хочете вийти? Вам потрібно буде заново увійти у свій акаунт.</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeLogoutModal()">Скасувати</button>
                <button class="modal-btn modal-btn-delete" onclick="confirmLogout()">Вихід</button>
            </div>
        </div>
    </div>

    <script>
        // РЕДАГУВАННЯ ПРОФІЛЯ
        function openEditModal() {
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            document.getElementById('editAlert').classList.remove('show');
        }

        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alertBox = document.getElementById('editAlert');

                if (data.success) {
                    alertBox.textContent = '✅ ' + data.message;
                    alertBox.classList.add('show', 'alert-success');
                    alertBox.classList.remove('alert-error');
                    setTimeout(() => {
                        closeEditModal();
                        location.reload();
                    }, 1500);
                } else {
                    alertBox.textContent = '❌ ' + data.message;
                    alertBox.classList.add('show', 'alert-error');
                    alertBox.classList.remove('alert-success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alertBox = document.getElementById('editAlert');
                alertBox.textContent = '❌ Помилка з\'єднання';
                alertBox.classList.add('show', 'alert-error');
                alertBox.classList.remove('alert-success');
            });
        });

        // ЗМІНА ПАРОЛЯ
        function openPasswordModal() {
            document.getElementById('passwordModal').classList.add('active');
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('active');
            document.getElementById('passwordAlert').classList.remove('show');
            document.getElementById('passwordForm').reset();
        }

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alertBox = document.getElementById('passwordAlert');

                if (data.success) {
                    alertBox.textContent = '✅ ' + data.message;
                    alertBox.classList.add('show', 'alert-success');
                    alertBox.classList.remove('alert-error');
                    document.getElementById('passwordForm').reset();
                    setTimeout(() => closePasswordModal(), 1500);
                } else {
                    alertBox.textContent = '❌ ' + data.message;
                    alertBox.classList.add('show', 'alert-error');
                    alertBox.classList.remove('alert-success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alertBox = document.getElementById('passwordAlert');
                alertBox.textContent = '❌ Помилка з\'єднання';
                alertBox.classList.add('show', 'alert-error');
                alertBox.classList.remove('alert-success');
            });
        });

        // ВИХІД
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        function confirmLogout() {
            document.getElementById('logoutForm').submit();
        }

        // Закриття модалок при клику поза ними
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Закриття модалок при натиску ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>