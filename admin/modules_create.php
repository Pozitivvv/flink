<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $image = trim($_POST['image']);

    $stmt = $pdo->prepare("INSERT INTO modules (title, description, image) VALUES (?, ?, ?)");
    $stmt->execute([$title, $description, $image]);

    header("Location: modules_list.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<body>
<h1>Створити модуль</h1>
<form method="POST">
    <input name="title" placeholder="Назва" required><br>
    <textarea name="description" placeholder="Опис"></textarea><br>
    <input name="image" placeholder="URL картинки"><br>
    <button>Створити</button>
</form>
</body>
</html>