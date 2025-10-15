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

    if ($name === '' || $login === '' || $email === '' || $password === '') {
        $message = 'Заповніть усі поля';
        $error = true;
    } else {
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
    <title>Реєстрація | Німецький словник</title>
    <link rel="stylesheet" href="assets/login/login.css">
</head>
<body>
    <div class="register-box">
        <div class="logo">
            <span class="logo-icon">🎓</span>
            <h1>Створити акаунт</h1>
            <p class="subtitle">Почніть вивчати німецьку вже сьогодні!</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <span class="input-icon">🧍</span>
                <input type="text" name="name" placeholder="Ім’я" required autocomplete="name">
            </div>
            <div class="input-group">
                <span class="input-icon">👤</span>
                <input type="text" name="login" placeholder="Логін" required autocomplete="username">
            </div>
            <div class="input-group">
                <span class="input-icon">📧</span>
                <input type="email" name="email" placeholder="Email" required autocomplete="email">
            </div>
            <div class="input-group">
                <span class="input-icon">🔒</span>
                <input type="password" name="password" placeholder="Пароль" required autocomplete="new-password">
            </div>
            <button type="submit">Створити акаунт</button>
        </form>

        <p class="footer-text">
            Вже маєте акаунт? <a href="index.php">Увійти</a>
        </p>
    </div>
    <script src="script/alerts.js"></script>
</body>
</html>
