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
$error_message = '';
$success_message = '';

try {
    // Отримуємо дані користувача
    $stmt = $pdo->prepare("
        SELECT id, name, login, email, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    // Обробка редагування профіля
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'edit_profile') {
            $new_name = trim($_POST['name'] ?? '');
            $new_email = trim($_POST['email'] ?? '');

            if (empty($new_name) || empty($new_email)) {
                $error_message = 'Заповніть всі поля!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ? 
                    WHERE id = ?
                ");
                if ($stmt->execute([$new_name, $new_email, $user_id])) {
                    $success_message = 'Профіль успішно оновлено!';
                    $user['name'] = $new_name;
                    $user['email'] = $new_email;
                } else {
                    $error_message = 'Помилка при оновленні профіля';
                }
            }
        }

        // Обробка зміни пароля
        if ($_POST['action'] === 'change_password') {
            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = 'Заповніть всі поля!';
            } elseif ($new_password !== $confirm_password) {
                $error_message = 'Новий пароль не збігається з підтвердженням!';
            } elseif (strlen($new_password) < 6) {
                $error_message = 'Пароль має бути мінімум 6 символів!';
            } else {
                // Перевіримо старий пароль
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $stored_password = $stmt->fetchColumn();

                if (!password_verify($old_password, $stored_password)) {
                    $error_message = 'Старий пароль невірний!';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        $success_message = 'Пароль успішно змінено!';
                    } else {
                        $error_message = 'Помилка при зміні пароля';
                    }
                }
            }
        }
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
    <title>Профіль</title>
    <link rel="stylesheet" href="style/profile.css">
    <link rel="stylesheet" href="../../assets/main-style.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.2s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 20px;
            padding: 24px;
            max-width: 400px;
            width: 100%;
            animation: slideUp 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            text-align: center;
            margin-bottom: 16px;
        }

        .modal-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .modal h2 {
            font-size: 20px;
            color: #fff;
            margin-bottom: 8px;
        }

        .modal p {
            color: #9ca3af;
            font-size: 14px;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-btn-cancel {
            background: #2a2a2a;
            color: #e4e4e4;
        }

        .modal-btn-cancel:active {
            background: #333;
        }

        .modal-btn-delete {
            background: #ef4444;
            color: white;
        }

        .modal-btn-delete:active {
            background: #dc2626;
            transform: scale(0.98);
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        .form-group input {
            padding: 12px;
            background: #0f0f0f;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            color: #e4e4e4;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: #1a1a1a;
        }

        .alert-box {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            display: none;
        }

        .alert-box.show {
            display: block;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .modal-btn-submit {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .modal-btn-submit:active {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: scale(0.98);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
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
            <div class="stat-card">
                <div class="stat-value"><?php echo $errors_count; ?></div>
                <div class="stat-label">Помилок</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $progress; ?>%</div>
                <div class="stat-label">Прогрес</div>
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
            <div class="modal-header">
                <div class="modal-icon">🚪</div>
                <h2>Вихід з акаунту</h2>
            </div>
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
            .then(response => response.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const successMsg = doc.body.textContent.includes('Профіль успішно оновлено');
                const alertBox = document.getElementById('editAlert');
                
                if (successMsg) {
                    alertBox.textContent = '✅ Профіль успішно оновлено!';
                    alertBox.classList.add('show', 'alert-success');
                    alertBox.classList.remove('alert-error');
                    setTimeout(() => closeEditModal(), 1500);
                    location.reload();
                } else {
                    alertBox.textContent = '❌ Помилка при оновленні профіля';
                    alertBox.classList.add('show', 'alert-error');
                    alertBox.classList.remove('alert-success');
                }
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
            .then(response => response.text())
            .then(html => {
                const alertBox = document.getElementById('passwordAlert');
                const doc = new DOMParser().parseFromString(html, 'text/html');
                
                if (html.includes('Пароль успішно змінено')) {
                    alertBox.textContent = '✅ Пароль успішно змінено!';
                    alertBox.classList.add('show', 'alert-success');
                    alertBox.classList.remove('alert-error');
                    document.getElementById('passwordForm').reset();
                    setTimeout(() => closePasswordModal(), 1500);
                } else if (html.includes('Старий пароль невірний')) {
                    alertBox.textContent = '❌ Старий пароль невірний!';
                    alertBox.classList.add('show', 'alert-error');
                    alertBox.classList.remove('alert-success');
                } else if (html.includes('не збігається')) {
                    alertBox.textContent = '❌ Паролі не збігаються!';
                    alertBox.classList.add('show', 'alert-error');
                    alertBox.classList.remove('alert-success');
                } else if (html.includes('мінімум 6')) {
                    alertBox.textContent = '❌ Пароль має бути мінімум 6 символів!';
                    alertBox.classList.add('show', 'alert-error');
                    alertBox.classList.remove('alert-success');
                }
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