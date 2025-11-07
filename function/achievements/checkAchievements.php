<?php
// checkAchievements.php
// ⚙️ config.php уже подключен в основном файле, поэтому здесь НЕ подключаем его повторно

/**
 * Перевіряє, чи користувач заробив нові ачивки
 * @param int $user_id ID користувача
 * @param string $type Тип умови
 * @param mixed $value Додаткове значення (опціонально)
 * @return array Результат з інформацією про отримане досягнення
 */
function checkAchievements($user_id, $type, $value = null) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM achievements WHERE condition_type = ?");
    $stmt->execute([$type]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($achievements as $ach) {
        $ach_id = $ach['id'];
        $cond_value = (int)$ach['condition_value'];

        // Перевіряємо, чи вже є це досягнення у користувача
        $stmt = $pdo->prepare("SELECT 1 FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
        $stmt->execute([$user_id, $ach_id]);
        $has = $stmt->fetchColumn();

        if ($has) continue;

        $achieved = false;

        // ⚙️ Логіка для різних типів досягнень
        switch ($type) {

            // ✅ Кількість слів
            case 'words_count':
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM words WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $count = $stmt->fetchColumn();
                if ($count >= $cond_value) $achieved = true;
                break;

            // ✅ Ідеальні слова
            case 'perfect_words':
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM words WHERE user_id = ? AND errors = 0");
                $stmt->execute([$user_id]);
                $perfect = $stmt->fetchColumn();
                if ($perfect >= $cond_value) $achieved = true;
                break;

            // ✅ Помилки виправлені
            case 'errors_fixed':
                $stmt = $pdo->prepare("SELECT errors_fixed FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $fixed = (int)($stmt->fetchColumn() ?: 0);
                if ($fixed >= $cond_value) $achieved = true;
                break;

            // ✅ Активність у нічний час (СОВА)
            case 'night_activity':
                $hour = (int)date('H');
                if ($hour >= 0 && $hour < 4) $achieved = true;
                break;

            // ✅ Активність вранці (ЖАЙВОРОНОК)
            case 'morning_activity':
                $hour = (int)date('H');
                if ($hour >= 5 && $hour < 7) $achieved = true;
                break;

            // ✅ Серія днів підряд (СЕРІЯ 7, СТАЛЕВИЙ)
            case 'streak_days':
                $stmt = $pdo->prepare("SELECT current_streak FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $streak = (int)($stmt->fetchColumn() ?: 0);
                if ($streak >= $cond_value) $achieved = true;
                break;

            // ✅ Перше відвідування (НА СТАРТІ)
            case 'first_login':
                $stmt = $pdo->prepare("SELECT first_login_done FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $done = (int)($stmt->fetchColumn() ?: 0);
                if (!$done) {
                    $achieved = true;
                    $pdo->prepare("UPDATE users SET first_login_done = 1 WHERE id = ?")->execute([$user_id]);
                }
                break;
        }

        if ($achieved) {
            $stmt = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $ach_id]);

            return [
                "success" => true,
                "achievement" => $ach['title'],
                "icon" => $ach['icon']
            ];
        }
    }

    return ["success" => false];
}

/**
 * Отримує прогрес користувача до ачивки
 */
function getAchievementProgress($user_id, $type, $condition_value) {
    global $pdo;
    
    $current = 0;
    
    switch ($type) {
        case 'words_count':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM words WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current = $stmt->fetchColumn();
            break;
        case 'errors_fixed':
            $stmt = $pdo->prepare("SELECT errors_fixed FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current = (int)($stmt->fetchColumn() ?: 0);
            break;
        case 'days_active':
        case 'streak_days':
            $stmt = $pdo->prepare("SELECT current_streak FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current = (int)($stmt->fetchColumn() ?: 0);
            break;
        case 'perfect_words':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM words WHERE user_id = ? AND errors = 0");
            $stmt->execute([$user_id]);
            $current = $stmt->fetchColumn();
            break;
    }
    
    return [
        'current' => min($current, $condition_value),
        'target' => $condition_value,
        'percentage' => round(($current / $condition_value) * 100, 0)
    ];
}
?>
