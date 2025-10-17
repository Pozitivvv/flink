<?php
session_start();
require_once '../config.php';

// Перевірка авторизації
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('unauthorized');
}

$user_id = $_SESSION['user_id'];

// Перевіряємо, чи передано ID слова
if (!isset($_POST['word_id'])) {
    http_response_code(400);
    exit('no_word_id');
}

$word_id = (int)$_POST['word_id'];

try {
    // Отримуємо слово з бази base_words
    $stmt = $pdo->prepare("SELECT * FROM base_words WHERE id = ?");
    $stmt->execute([$word_id]);
    $baseWord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$baseWord) {
        http_response_code(404);
        exit('word_not_found');
    }

    $article = trim($baseWord['article'] ?? '');
    $german = trim($baseWord['german'] ?? '');

    // 🧩 Якщо в base_words артикль не записан окремо — витягуємо його зі слова
    if ($article === '' && preg_match('/^(der|die|das)\s+(.*)$/iu', $german, $matches)) {
        $article = ucfirst(strtolower($matches[1])); // Der / Die / Das
        $german = $matches[2];
    }

    // Перевіримо, чи вже є це слово у користувача
    $check = $pdo->prepare("SELECT id FROM words WHERE user_id = ? AND german = ?");
    $check->execute([$user_id, $german]);

    if ($check->fetch()) {
        echo 'exists';
        exit;
    }

    // Додаємо слово до таблиці words
    $insert = $pdo->prepare("
        INSERT INTO words (user_id, article, german, translation, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insert->execute([
        $user_id,
        $article,
        $german,
        $baseWord['translation']
    ]);

    echo 'success';

} catch (PDOException $e) {
    http_response_code(500);
    echo 'db_error: ' . $e->getMessage();
}
