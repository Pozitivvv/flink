<?php
session_start();

// Знищуємо всі дані сесії
$_SESSION = [];
session_unset();
session_destroy();

// Видаляємо cookies сесії (для надійності)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Перенаправляємо на головну або сторінку входу
header("Location: index.php");
exit();
