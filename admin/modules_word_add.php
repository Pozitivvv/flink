<?php
// modules_word_add.php
session_start();
require_once '../config.php';

$module_id = (int)$_POST['module_id'];
$article = trim($_POST['article']);
$german = trim($_POST['german']);
$translation = trim($_POST['translation']);
$type = trim($_POST['type']);

$stmt = $pdo->prepare("
    INSERT INTO module_words (module_id, article, german, translation, type)
    VALUES (?, ?, ?, ?, ?)
");

// ✅ Исправлено — добавили знак $
$stmt->execute([$module_id, $article, $german, $translation, $type]);

header("Location: modules_edit.php?id=" . $module_id);
exit;
?>
