<?php
/**
 * index.php — головна сторінка Wortly
 */
session_start();
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] ?? 'Користувач' : null;

// === Отримуємо статистику з бази ===

// 1️⃣ Кількість усіх слів користувачів
$stmt = $pdo->query("SELECT COUNT(*) FROM words");
$total_words = (int)$stmt->fetchColumn();

// 2️⃣ Кількість усіх тем/уроків
$stmt = $pdo->query("SELECT COUNT(*) FROM days");
$total_days = (int)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'function/tags/icons.html'; ?>
    <?php include 'function/tags/seo.html'; ?>
    <title>Wortly — Вивчай німецьку мову легко</title>
    <link rel="stylesheet" href="assets/main-page.css">

</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <span>Wortly</span>
            </a>
            <div class="nav-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-secondary">Повернутися</a>
                    <button onclick="openLogoutModal()" class="btn btn-danger">Вихід</button>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Вхід</a>
                    <a href="register.php" class="btn btn-primary">Реєстрація</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">🇩🇪 DE</div>
            <h1>Вивчай німецьку мову легко</h1>
            <p class="hero-subtitle">Створюй особистий словник та тренуйся з картками</p>
            <p class="hero-description">Організуй слова за темами, тренуйся з інтерактивними завданнями та відслідковуй свій прогрес у реальному часі</p>
            
            <div class="hero-buttons">
                <a href="register.php" class="btn-hero btn-hero-primary">Розпочати безкоштовно</a>
                <a href="login.php" class="btn-hero btn-hero-secondary">Вже маю акаунт</a>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features">
        <div class="features-container">
            <div class="section-header">
                <h2>✨ Можливості</h2>
                <p>Всі інструменти для ефективного вивчення німецької мови</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <div class="feature-text">
                        <h3>Особистий словник</h3>
                        <p>Додавай свої слова й організуй їх за темами. Синхронізація на всіх пристроях.</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">✏️</div>
                    <div class="feature-text">
                        <h3>Інтерактивна практика</h3>
                        <p>Флешкарти й тести допоможуть закріпити знання. Відслідковуй прогрес у реальному часі.</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔊</div>
                    <div class="feature-text">
                        <h3>Озвучування слів</h3>
                        <p>Слухай вимову від носіїв мови й вимовляй разом з ними. Вільно володій мовою.</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🏷️</div>
                    <div class="feature-text">
                        <h3>Розбиття за темами</h3>
                        <p>Організуй слова за категоріями для кращого запам'ятовування й структури навчання.</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <div class="feature-text">
                        <h3>Розумне повторення</h3>
                        <p>Система повторення адаптується до твого рівня знань. Вивчай ефективніше.</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">
                        <h3>Статистика й помилки</h3>
                        <p>Детальна аналітика допоможе зрозуміти, над чим потрібно працювати більше.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="stats">
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_words); ?></div>
                    <div class="stat-label">Слів додано користувачами</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_days); ?></div>
                    <div class="stat-label">Створених тем</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Безкоштовно</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Доступно</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="cta-container">
            <span class="cta-emoji">🎓</span>
            <h2>Готовий розпочати?</h2>
            <p>Приєднайся до тисяч учнів, які вже вивчають німецьку. Перший крок до вільного володіння!</p>
            <?php if (!$is_logged_in): ?>
                <a href="register.php" class="btn-hero btn-hero-primary">Розпочати безкоштовно</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn-hero btn-hero-primary">Перейти до словника</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <p>&copy; 2025 Wortly. Всі права захищені. | <a href="#" style="color:#3b82f6;">Приватність</a> | <a href="#" style="color:#3b82f6;">Умови</a></p>
    </footer>

    <!-- LOGOUT MODAL -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">🚪</div>
                <h2>Вихід з акаунту</h2>
            </div>
            <p>Ви впевнені, що хочете вийти? Вам потрібно буде заново увійти у свій акаунт.</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeLogoutModal()">Скасувати</button>
                <button class="modal-btn modal-btn-delete" onclick="confirmLogout()">Вихід</button>
            </div>
        </div>
    </div>

    <form id="logoutForm" method="POST" action="auth/logout.php" style="display:none;"></form>

    <script>
        function openLogoutModal() { document.getElementById('logoutModal').classList.add('active'); }
        function closeLogoutModal() { document.getElementById('logoutModal').classList.remove('active'); }
        function confirmLogout() { document.getElementById('logoutForm').submit(); }
        document.getElementById('logoutModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeLogoutModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLogoutModal(); });
    </script>
</body>
</html>
