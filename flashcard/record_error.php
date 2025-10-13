<?php
// record_error.php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error'=>'Not authorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$word_id = isset($_POST['word_id']) ? (int)$_POST['word_id'] : 0;
if ($word_id <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid word_id']);
    exit;
}

try {
    // Вставляем, если ещё нет (на случай, если UNIQUE нет, делаем двойную проверку)
    $check = $pdo->prepare("SELECT id FROM user_errors WHERE user_id = ? AND word_id = ? LIMIT 1");
    $check->execute([$user_id, $word_id]);
    if (!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO user_errors (user_id, word_id) VALUES (?, ?)");
        $ins->execute([$user_id, $word_id]);
    }
    echo json_encode(['ok'=>true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
