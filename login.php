<?php
session_start();
require_once 'config.php';

// Если пользователь уже вошёл — переходим в меню
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = false;
$login = ''; // Инициализируем переменную, чтобы избежать notice при первой загрузке

// 🔐 Обработка входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit();
    } else {
        $message = 'Невірний логін або пароль';
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід | Німецький словник</title>
    <?php include 'function/tags/icons.html'; ?>
    <?php include 'function/tags/seo.html'; ?>
    <link rel="stylesheet" href="assets/login/login.css">
    
    <style>
        .input-group {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .toggle-password:hover {
            opacity: 1;
        }
        .input-group input[type="password"],
        .input-group input[type="text"].password-visible {
            padding-right: 40px; 
        }
    </style>
</head>
<body>
    <div class="auth-box">
        <div class="logo">
            <span class="logo-icon">📚</span>
            <h2>Вітаємо!</h2>
            <p class="subtitle">Увійдіть до свого акаунту</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $error ? 'error' : ''; ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <span class="input-icon">👤</span>
                <input type="text" name="login" placeholder="Логін" required autocomplete="username" value="<?= htmlspecialchars($login) ?>">
            </div>
            <div class="input-group">
                <span class="input-icon">🔒</span>
                <input type="password" id="password" name="password" placeholder="Пароль" required autocomplete="current-password">
                <span class="toggle-password" id="togglePassword" title="Показати/Сховати пароль">👁️</span>
            </div>
            <button type="submit">Увійти</button>
        </form>

        <div class="divider">або</div>

        <a href="register.php" class="button-link">Створити новий акаунт</a>
    </div>
    
    <script src="script/alerts.js"></script>
    
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                if (type === 'text') {
                    passwordInput.classList.add('password-visible');
                } else {
                    passwordInput.classList.remove('password-visible');
                }

                this.textContent = type === 'password' ? '👁️' : '🙈';
            });
        }
    </script>
</body>
</html>