<?php
session_start();
require_once 'config.php';

// Если пользователь уже вошёл — перенаправляем в кабинет
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 1. Проверка на пустые поля
    if ($name === '' || $login === '' || $email === '' || $password === '') {
        $message = 'Заповніть усі поля';
        $error = true;
    } 
    // 2. Валидация имени (от 2 до 50 символов)
    elseif (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
        $message = 'Ім\'я має містити від 2 до 50 символів';
        $error = true;
    } 
    // 3. Валидация логина (только латиница, цифры, подчеркивание, от 3 до 20 символов)
    elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $login)) {
        $message = 'Логін має бути від 3 до 20 символів і містити лише латинські літери, цифри та _';
        $error = true;
    } 
    // 4. Валидация Email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Введіть коректну email адресу';
        $error = true;
    } 
    // 5. Валидация пароля (минимум 6 символов)
    elseif (mb_strlen($password) < 6) {
        $message = 'Пароль має містити щонайменше 6 символів';
        $error = true;
    } 
    else {
        // Проверяем, есть ли уже такой логин или email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR login = ?");
        $stmt->execute([$email, $login]);
        if ($stmt->fetch()) {
            $message = 'Такий логін або email вже використовується';
            $error = true;
        } else {
            // Создаём пользователя
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, login, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $login, $email, $hashed]);

            // Автоматический вход
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_login'] = $login;
            $_SESSION['user_name'] = $name;

            header('Location: dashboard.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'function/tags/icons.html'; ?>
    <?php include 'function/tags/seo.html'; ?>
    <title>Реєстрація | Німецький словник</title>
    <link rel="stylesheet" href="assets/login/login.css">
    
    <style>
        .input-group {
            position: relative; /* Убеждаемся, что глазок позиционируется относительно поля ввода */
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
        /* Чтобы текст пароля не залезал под глазок */
        .input-group input[type="password"],
        .input-group input[type="text"].password-visible {
            padding-right: 40px; 
        }
    </style>
</head>
<body>
    <div class="register-box">
        <div class="logo">
            <span class="logo-icon">🎓</span>
            <h1>Створити акаунт</h1>
            <p class="subtitle">Почніть вивчати німецьку вже сьогодні!</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $error ? 'error' : 'success'; ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <span class="input-icon">🧍</span>
                <input type="text" name="name" placeholder="Ім’я" required minlength="2" maxlength="50" autocomplete="name" value="<?= htmlspecialchars($name ?? '') ?>">
            </div>
            <div class="input-group">
                <span class="input-icon">👤</span>
                <input type="text" name="login" placeholder="Логін" required pattern="[a-zA-Z0-9_]{3,20}" title="Тільки латинські літери, цифри та _, від 3 до 20 символів" autocomplete="username" value="<?= htmlspecialchars($login ?? '') ?>">
            </div>
            <div class="input-group">
                <span class="input-icon">📧</span>
                <input type="email" name="email" placeholder="Email" required autocomplete="email" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            <div class="input-group">
                <span class="input-icon">🔒</span>
                <input type="password" id="password" name="password" placeholder="Пароль" required minlength="6" autocomplete="new-password">
                <span class="toggle-password" id="togglePassword" title="Показати/Сховати пароль">👁️</span>
            </div>
            <button type="submit">Створити акаунт</button>
        </form>

        <p class="footer-text">
            Вже маєте акаунт? <a href="login.php">Увійти</a>
        </p>
    </div>
    
    <script src="script/alerts.js"></script>
    
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            // Переключаем тип атрибута: если password, то делаем text, и наоборот
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Добавляем класс для паддинга, если тип text (чтобы текст не наезжал на глазок)
            if(type === 'text') {
                passwordInput.classList.add('password-visible');
            } else {
                passwordInput.classList.remove('password-visible');
            }

            // Переключаем иконку
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>
</body>
</html>