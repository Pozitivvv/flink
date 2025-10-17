<?php
require_once 'config.php'; // Подключаем конфиг с $pdo

$jsonFile = __DIR__ . '/words.json';

if (!file_exists($jsonFile)) {
    die("Файл words.json не найден!");
}

// Чтение и декодирование JSON
$json = file_get_contents($jsonFile);
$data = json_decode($json, true);

if (!$data || !isset($data['words'])) {
    die("Некорректный формат JSON!");
}

// Подготовка запроса
$stmt = $pdo->prepare("
    INSERT INTO base_words (article, german, transcription, translation)
    VALUES (:article, :german, :transcription, :translation)
");

foreach ($data['words'] as $word) {
    $article = $word['article'] ?? ''; // если article нет — пустая строка
    $german = $word['german'] ?? '';
    $transcription = $word['transcription'] ?? '';
    $translation = $word['ukrainian'] ?? '';

    if ($german && $translation) { // проверяем обязательные поля
        $stmt->execute([
            ':article' => $article,
            ':german' => $german,
            ':transcription' => $transcription,
            ':translation' => $translation
        ]);
    }
}

echo "Импорт слов завершен!";
