<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$day_id = isset($_POST['day_id']) && $_POST['day_id'] !== '' ? (int)$_POST['day_id'] : 0;
$mode = $_POST['mode'] ?? 'normal';

// Параметры для запроса
$params = [$user_id];

// Якщо день не вибраний (day_id = 0) - випадкові 20 слів
if ($day_id === 0) {
    if ($mode === 'errors') {
        $query = "SELECT w.id, w.german, w.article, w.translation
                  FROM user_errors e
                  JOIN words w ON e.word_id = w.id
                  WHERE e.user_id = ?
                  ORDER BY RAND()
                  LIMIT 20";
    } elseif ($mode === 'articles') {
        $query = "SELECT id, german, article, translation
                  FROM words
                  WHERE user_id = ? AND article IS NOT NULL AND article != ''
                  ORDER BY RAND()
                  LIMIT 20";
    } else {
        // Звичайний режим - 20 випадкових слів
        $query = "SELECT id, german, article, translation 
                  FROM words 
                  WHERE user_id = ? 
                  ORDER BY RAND() 
                  LIMIT 20";
    }
} else {
    // Якщо день вибраний - всі слова з цієї теми
    if ($mode === 'errors') {
        $query = "SELECT w.id, w.german, w.article, w.translation
                  FROM user_errors e
                  JOIN words w ON e.word_id = w.id
                  WHERE e.user_id = ? AND w.day_id = ?";
        $params[] = $day_id;
    } elseif ($mode === 'articles') {
        $query = "SELECT id, german, article, translation
                  FROM words
                  WHERE user_id = ? AND day_id = ? AND article IS NOT NULL AND article != ''";
        $params[] = $day_id;
    } else {
        $query = "SELECT id, german, article, translation 
                  FROM words 
                  WHERE user_id = ? AND day_id = ?";
        $params[] = $day_id;
    }
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($words, JSON_UNESCAPED_UNICODE);
?>
