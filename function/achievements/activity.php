<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . 'checkAchievements.php';

function registerDailyActivity($user_id) {
    global $pdo;

    $today = date('Y-m-d');

    // Берем данные пользователя
    $stmt = $pdo->prepare("SELECT last_active_date, current_streak FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $last = $user['last_active_date'];
    $streak = (int)$user['current_streak'];

    // Если активности сегодня не было — рассматриваем день
    if ($last !== $today) {

        // Если вчера была активность — продолжаем streak
        if ($last === date('Y-m-d', strtotime('-1 day'))) {
            $streak++;
        } else {
            // Иначе начинаем новый streak
            $streak = 1;
        }

        // Обновляем streak
        $stmt = $pdo->prepare("UPDATE users SET last_active_date = ?, current_streak = ? WHERE id = ?");
        $stmt->execute([$today, $streak, $user_id]);

        // Проверка ачивок
        checkAchievements($user_id, 'streak_days');
    }
}
