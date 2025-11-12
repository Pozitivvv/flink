<?php
// admin/modules_import_json.php
session_start();
require_once '../config.php';
require_once 'config.php'; // check_admin()

check_admin();

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['module_id']) || !isset($data['words'])) {
        throw new Exception('Невірні дані');
    }
    
    $module_id = (int)$data['module_id'];
    $words = $data['words'];
    
    if (!is_array($words) || empty($words)) {
        throw new Exception('Слова мають бути не порожнім масивом');
    }
    
    // Перевіряємо, чи модуль існує
    $stmt = $pdo->prepare("SELECT id FROM modules WHERE id = ?");
    $stmt->execute([$module_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Модуль не знайдено');
    }
    
    $added = 0;
    
    foreach ($words as $w) {
        $article = trim($w['article'] ?? '');
        $german = trim($w['german'] ?? '');
        $translation = trim($w['translation'] ?? '');
        $type = trim($w['type'] ?? 'noun');
        
        // Перевіряємо обов'язкові поля
        if (empty($german) || empty($translation)) {
            continue;
        }
        
        // Валідація типу
        $valid_types = ['noun', 'verb', 'adj', 'adv', 'prep', 'conj'];
        if (!in_array($type, $valid_types)) {
            $type = 'noun';
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO module_words (module_id, article, german, translation, type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$module_id, $article, $german, $translation, $type]);
            $added++;
        } catch (Exception $e) {
            // Продовжуємо, навіть якщо одне слово не додалось
            continue;
        }
    }
    
    if ($added === 0) {
        throw new Exception('Не вдалось додати слова. Перевірте формат.');
    }
    
    echo "✅ Успішно додано {$added} слів(о)!";
    
} catch (Exception $e) {
    echo "❌ Помилка: " . htmlspecialchars($e->getMessage());
}

?>