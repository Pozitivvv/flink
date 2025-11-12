<?php
// admin/config.php

require_once __DIR__ . '/../config.php';

/**
 * Проверка прав администратора
 * Если пользователь не админ - выходит с ошибкой 403
 */
function check_admin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_admin']) {
        http_response_code(403);
        die("❌ Доступ запрещен. У вас нет прав администратора.");
    }
}

/**
 * Загрузка изображения на сервер
 * @param array $file - $_FILES['image']
 * @return string|null - путь к загруженному файлу или null
 * @throws Exception - при ошибке
 */
function upload_image($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Проверка, что файл был загружен
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return null;
    }
    
    // Проверка размера
    if ($file['size'] > $max_size) {
        throw new Exception("Файл слишком большой (максимум 5MB)");
    }
    
    // Проверка типа файла
    if (!in_array($file['type'], $allowed)) {
        throw new Exception("Допускаются только JPG, PNG, WebP");
    }
    
    // Создаем папку для загрузок
    $upload_dir = __DIR__ . '/../uploads/modules/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Генерируем уникальное имя файла
    $filename = 'module_' . time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filepath = $upload_dir . $filename;
    
    // Загружаем файл
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Ошибка при загрузке файла на сервер");
    }
    
    // Возвращаем путь для сохранения в БД
    return '/uploads/modules/' . $filename;
}

/**
 * Удаление старого изображения
 * @param string $image_path - путь к изображению из БД
 */
function delete_image($image_path) {
    if (empty($image_path) || $image_path === '/') {
        return;
    }
    
    $file_path = __DIR__ . '/..' . $image_path;
    if (file_exists($file_path) && is_file($file_path)) {
        unlink($file_path);
    }
}

?>