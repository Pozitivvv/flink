<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) die("403");

$data = json_decode(file_get_contents('php://input'), true);
$module_id = (int)$data['module_id'];
$words = $data['words'];

foreach ($words as $w) {
    $stmt = $pdo->prepare("INSERT INTO module_words (module_id, article, german, translation, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $module_id,
        $w['article'] ?? '',
        $w['german'] ?? '',
        $w['translation'] ?? '',
        $w['type'] ?? ''
    ]);
}

echo "✅ Всі слова успішно додані у модуль!";
