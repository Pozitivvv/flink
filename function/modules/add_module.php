<?php
// modules/add_module.php
error_log("=== ADD MODULE START ===");
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION: " . print_r($_SESSION, true));

ob_start();
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();

try {
    require_once '../../config.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'DB Connection Error: ' . $e->getMessage()
    ));
    exit;
}

try {
    // Проверяем авторизацию
    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(array('success' => false, 'message' => 'Not authorized'));
        exit;
    }

    // Проверяем метод
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Invalid method'));
        exit;
    }

    $module_id = intval(isset($_POST['module_id']) ? $_POST['module_id'] : 0);
    $user_id = (int)$_SESSION['user_id'];

    if (!$module_id || !$user_id) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(array('success' => false, 'message' => 'Invalid parameters'));
        exit;
    }

    // Начало транзакции
    $pdo->beginTransaction();

    // Проверяем, существует ли модуль
    $checkStmt = $pdo->prepare("SELECT title FROM modules WHERE id = ?");
    if (!$checkStmt) {
        throw new Exception('Query prepare error: ' . implode(' | ', $pdo->errorInfo()));
    }
    $checkStmt->execute(array($module_id));
    $module = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$module) {
        $pdo->rollBack();
        ob_end_clean();
        http_response_code(404);
        echo json_encode(array('success' => false, 'message' => 'Module not found'));
        exit;
    }

    // Проверяем, не добавлен ли уже
    $existStmt = $pdo->prepare("SELECT id FROM user_modules WHERE user_id = ? AND module_id = ?");
    $existStmt->execute(array($user_id, $module_id));
    
    if ($existStmt->fetch()) {
        $pdo->rollBack();
        ob_end_clean();
        http_response_code(409);
        echo json_encode(array('success' => false, 'message' => 'Already added'));
        exit;
    }

    // Получаем слова модуля
    $wordsStmt = $pdo->prepare("SELECT article, german, translation, type FROM module_words WHERE module_id = ?");
    $wordsStmt->execute(array($module_id));
    $words = $wordsStmt->fetchAll(PDO::FETCH_ASSOC);

    $words_added = 0;

    // 1️⃣ Создаём новую тему с названием модуля
    $dayStmt = $pdo->prepare("INSERT INTO days (user_id, title) VALUES (?, ?)");
    $dayStmt->execute(array($user_id, $module['title']));
    $day_id = $pdo->lastInsertId();

    // 2️⃣ Добавляем все слова в эту тему
    foreach ($words as $w) {
        // Проверка на дубль в этой теме
        $checkWord = $pdo->prepare("SELECT id FROM words WHERE user_id = ? AND german = ? AND day_id = ?");
        $checkWord->execute(array($user_id, $w['german'], $day_id));

        if (!$checkWord->fetch()) {
            // Добавляем слово в тему
            $insertWord = $pdo->prepare("
                INSERT INTO words (user_id, day_id, article, german, translation, type)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $article = isset($w['article']) ? $w['article'] : null;
            $type = isset($w['type']) && $w['type'] ? $w['type'] : 'noun';
            
            $insertWord->execute(array(
                $user_id,
                $day_id,
                $article,
                $w['german'],
                $w['translation'],
                $type
            ));
            $words_added++;
        }
    }

    // 3️⃣ Добавляем модуль в user_modules
    $addModuleStmt = $pdo->prepare("INSERT INTO user_modules (user_id, module_id, added_at) VALUES (?, ?, NOW())");
    $addModuleStmt->execute(array($user_id, $module_id));

    // Коммитим транзакцию
    $pdo->commit();

    ob_end_clean();
    http_response_code(200);
    echo json_encode(array(
        'success' => true, 
        'message' => "Module '{$module['title']}' added successfully!",
        'words_added' => $words_added
    ));

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    ob_end_clean();
    http_response_code(500);
    echo json_encode(array(
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ));
}