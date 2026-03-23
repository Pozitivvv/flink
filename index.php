<?php

/**
 * index.php — Wortly Landing v4
 */
session_start();
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'uk';
if (isset($_GET['lang'])) $_SESSION['lang'] = $lang;

$stmt = $pdo->query("SELECT COUNT(*) FROM words");
$total_words = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM days");
$total_days = (int)$stmt->fetchColumn();

$t = [
    'uk' => [
        'html_lang'    => 'uk',
        'page_title'   => 'Wortly - Вивчай німецьку',
        'beta'         => 'Бета',
        'nav_login'    => 'Увійти',
        'nav_start'    => 'Почати',
        'nav_back'     => 'Повернутись',
        'nav_logout' => 'Вихід',
        'entry_word'   => 'Wortly',
        'entry_pos'    => 'сущ., застосунок',
        'entry_phon'   => '/ˈvɔrtli/',
        'entry_def'    => 'Інструмент для вивчення німецьких слів через особистий словник, флешкарти та голосову практику.',
        'entry_ex'     => 'Я вивчив 200 слів за місяць завдяки Wortly.',
        'entry_syn'    => 'синоніми: словник, тренажер, помічник',
        'cta_free'     => 'Почати безкоштовно',
        'cta_login'    => 'Вже маю акаунт',
        'cta_dash'     => 'До мого словника',
        'stat_words'   => 'слів у базі',
        'stat_topics' => 'тем',
        'stat_free'    => 'безкоштовно',
        'stat_online' => '24/7',
        'why_label'    => 'Чому Wortly',
        'why_title'    => 'Більшість методів не працюють. Ось чому.',
        'why_cards'    => [
            ['❌', 'Зубрячка списків', 'Годину вчите, запам\'ятали 10 слів, наступного дня забули 8. Знайомо?'],
            ['❌', 'Застосунки-ігри', 'Весело, але поверхово. Без контексту і власного словника - прогресу немає.'],
            ['✅', 'Wortly', 'Ваші слова, ваш темп. Голос + флешкарти + статистика = результат.'],
        ],
        'feat_label'   => 'Можливості',
        'feat_title'   => 'Все що потрібно - в одному місці',
        'features'     => [
            ['🗂️', 'Модулі слів', 'Готові тематичні набори: подорожі, їжа, робота. Починай відразу.'],
            ['🃏', 'Флешкарти', 'Перевернув - відповів - оцінив. Класика, що працює 150 років.'],
            ['🎙️', 'Голосова практика', 'Вимовляй вголос, отримуй оцінку. Акцент зникає поступово.'],
            ['📖', 'Опис слів', 'Рід, відмінювання, приклади. Розумієш - не зубриш.'],
            ['🏷️', 'Свої теми', 'Організуй слова як тобі зручно. Навчання стає особистим.'],
            ['📊', 'Статистика', 'Бачиш де застряг. Система нагадує про слабкі місця.'],
        ],
        'how_label'    => 'Як це працює',
        'how_title'    => 'Три кроки до результату',
        'steps'        => [
            ['1', 'Додай слова', 'Вибери готовий модуль або додай власні слова з перекладом.'],
            ['2', 'Тренуйся', 'Флешкарти, голосова практика, тести - обирай під настрій.'],
            ['3', 'Відслідковуй', 'Статистика покаже прогрес і нагадає про незасвоєні слова.'],
        ],
        'trust_label'  => 'Відгуки',
        'trust_title'  => 'Що кажуть користувачі',
        'reviews'      => [
            ['"Нарешті застосунок, де я сам вирішую, що вивчати. Модулі - просто вогонь."', 'Олена, вчителька'],
            ['"Голосова практика змінила все. Перестала соромитись говорити по-німецьки."', 'Марко, студент'],
            ['"Флешкарти + статистика помилок. Простіше не буває."', 'Ігор, розробник'],
        ],
        'cta_label'    => 'Старт',
        'cta_title'    => 'Одне слово на день - і через рік ти вільно говориш.',
        'cta_sub'      => 'Безкоштовно. Без підписки. Без відволікань.',
        'cta_btn'      => 'Розпочати зараз →',
        'cta_btn_dash' => 'До мого словника →',
        'f_priv'       => 'Конфіденційність',
        'f_terms' => 'Умови',
        'f_copy' => '© 2025 Wortly',
        'theme_dark'   => 'Темна',
        'theme_light' => 'Світла',
        'modal_title'  => 'Вийти?',
        'modal_body' => 'Вам потрібно буде знову увійти.',
        'modal_cancel' => 'Скасувати',
        'modal_yes' => 'Вийти',
        'chip_1'       => 'собака',
        'chip_2'       => 'вчити',
        'chip_3'       => 'мова',
        'chip_4'       => 'писати',
        'chip_5'       => 'розуміти',
    ],
    'de' => [
        'html_lang'    => 'de',
        'page_title'   => 'Wortly - Deutsch lernen',
        'beta'         => 'Beta',
        'nav_login'    => 'Einloggen',
        'nav_start'    => 'Starten',
        'nav_back'     => 'Zurück',
        'nav_logout'   => 'Abmelden',
        'entry_word'   => 'Wortly',
        'entry_pos'    => 'Subst., App',
        'entry_phon'   => '/ˈvɔrtli/',
        'entry_def'    => 'Ein Werkzeug zum Erlernen deutscher Wörter mit persönlichem Wörterbuch, Flashcards und Sprachübungen.',
        'entry_ex'     => 'Ich habe dank Wortly 200 Wörter in einem Monat gelernt.',
        'entry_syn'    => 'Synonyme: Wörterbuch, Trainer, Assistent',
        'cta_free'     => 'Kostenlos starten',
        'cta_login' => 'Ich habe ein Konto',
        'cta_dash'     => 'Zu meinem Wörterbuch',
        'stat_words'   => 'Wörter',
        'stat_topics' => 'Themen',
        'stat_free'    => 'kostenlos',
        'stat_online' => '24/7',
        'why_label'    => 'Warum Wortly',
        'why_title'    => 'Die meisten Methoden funktionieren nicht.',
        'why_cards'    => [
            ['❌', 'Vokabeln büffeln', 'Eine Stunde lernen, 10 Wörter merken, morgen 8 vergessen.'],
            ['❌', 'Spiel-Apps', 'Spaßig, aber oberflächlich. Kein Wörterbuch - kein Fortschritt.'],
            ['✅', 'Wortly', 'Ihre Wörter, Ihr Tempo. Sprachübungen + Flashcards + Statistik.'],
        ],
        'feat_label'   => 'Funktionen',
        'feat_title'   => 'Alles, was Sie brauchen - an einem Ort',
        'features'     => [
            ['🗂️', 'Wort-Module', 'Fertige thematische Sets: Reisen, Essen, Arbeit.'],
            ['🃏', 'Flashcards', 'Umblättern - antworten - bewerten.'],
            ['🎙️', 'Sprachtraining', 'Laut sprechen, Aussprache-Feedback erhalten.'],
            ['📖', 'Wortbeschreibungen', 'Genus, Deklination, Beispielsätze.'],
            ['🏷️', 'Eigene Themen', 'Eigene Kategorien erstellen.'],
            ['📊', 'Statistik', 'Schwachstellen sehen und gezielt üben.'],
        ],
        'how_label'    => 'Wie es funktioniert',
        'how_title'    => 'Drei Schritte zum Erfolg',
        'steps'        => [
            ['1', 'Wörter hinzufügen', 'Fertiges Modul wählen oder eigene Wörter hinzufügen.'],
            ['2', 'Üben', 'Flashcards, Sprachtraining, Tests nach Stimmung.'],
            ['3', 'Verfolgen', 'Statistik zeigt Fortschritte und erinnert an Schwachstellen.'],
        ],
        'trust_label'  => 'Bewertungen',
        'trust_title'  => 'Was Nutzer sagen',
        'reviews'      => [
            ['"Endlich eine App, bei der ich selbst entscheide, was ich lerne."', 'Olena, Lehrerin'],
            ['"Das Sprachtraining hat alles verändert."', 'Marco, Student'],
            ['"Flashcards + Statistik. Einfacher geht es nicht."', 'Igor, Entwickler'],
        ],
        'cta_label'    => 'Start',
        'cta_title' => 'Ein Wort pro Tag - ein Jahr - fließend.',
        'cta_sub'      => 'Kostenlos. Kein Abo. Keine Ablenkungen.',
        'cta_btn'      => 'Jetzt starten →',
        'cta_btn_dash' => 'Zu meinem Wörterbuch →',
        'f_priv'       => 'Datenschutz',
        'f_terms' => 'AGB',
        'f_copy' => '© 2025 Wortly',
        'theme_dark'   => 'Dunkel',
        'theme_light' => 'Hell',
        'modal_title'  => 'Abmelden?',
        'modal_body' => 'Sie müssen sich erneut anmelden.',
        'modal_cancel' => 'Abbrechen',
        'modal_yes' => 'Abmelden',
        'chip_1'       => 'dog',
        'chip_2'       => 'learn',
        'chip_3'       => 'language',
        'chip_4'       => 'write',
        'chip_5'       => 'understand',
    ],
    'en' => [
        'html_lang'    => 'en',
        'page_title'   => 'Wortly - Learn German',
        'beta'         => 'Beta',
        'nav_login'    => 'Log in',
        'nav_start'    => 'Start',
        'nav_back'     => 'Back',
        'nav_logout'   => 'Log out',
        'entry_word'   => 'Wortly',
        'entry_pos'    => 'n., application',
        'entry_phon'   => '/ˈvɔrtli/',
        'entry_def'    => 'A tool for learning German words through a personal dictionary, flashcards, and voice practice.',
        'entry_ex'     => 'I learned 200 words in a month thanks to Wortly.',
        'entry_syn'    => 'synonyms: dictionary, trainer, assistant',
        'cta_free'     => 'Start for free',
        'cta_login' => 'I have an account',
        'cta_dash'     => 'Open my vocabulary',
        'stat_words'   => 'words',
        'stat_topics' => 'topics',
        'stat_free'    => 'free',
        'stat_online' => '24/7',
        'why_label'    => 'Why Wortly',
        'why_title'    => 'Most methods don\'t work. Here\'s why.',
        'why_cards'    => [
            ['❌', 'Cramming lists', 'Study an hour, memorize 10, forget 8. Sound familiar?'],
            ['❌', 'Gamified apps', 'Fun but shallow. No personal dictionary - no progress.'],
            ['✅', 'Wortly', 'Your words, your pace. Voice + flashcards + stats = results.'],
        ],
        'feat_label'   => 'Features',
        'feat_title'   => 'Everything you need - in one place',
        'features'     => [
            ['🗂️', 'Word Modules', 'Ready-made themed sets: travel, food, work.'],
            ['🃏', 'Flashcards', 'Flip - answer - rate. Classic technique.'],
            ['🎙️', 'Voice Practice', 'Speak aloud, get rated. Accent improves every time.'],
            ['📖', 'Word Descriptions', 'Gender, declension, examples.'],
            ['🏷️', 'Custom Topics', 'Organize words your own way.'],
            ['📊', 'Statistics', 'See your weak spots. System reminds you.'],
        ],
        'how_label'    => 'How it works',
        'how_title'    => 'Three steps to results',
        'steps'        => [
            ['1', 'Add words', 'Choose a ready module or add your own with translation.'],
            ['2', 'Practice', 'Flashcards, voice, tests - pick by mood.'],
            ['3', 'Track', 'Stats show progress and remind you about unlearned words.'],
        ],
        'trust_label'  => 'Reviews',
        'trust_title'  => 'What users say',
        'reviews'      => [
            ['"Finally an app where I decide what I learn. Modules are 🔥"', 'Olena, teacher'],
            ['"Voice practice changed everything. No more fear of speaking."', 'Marco, student'],
            ['"Flashcards + error stats. It doesn\'t get simpler."', 'Igor, developer'],
        ],
        'cta_label'    => 'Start',
        'cta_title' => 'One word a day - a year later you speak freely.',
        'cta_sub'      => 'Free. No subscription. No distractions.',
        'cta_btn'      => 'Start now →',
        'cta_btn_dash' => 'Open my vocabulary →',
        'f_priv'       => 'Privacy',
        'f_terms' => 'Terms',
        'f_copy' => '© 2025 Wortly',
        'theme_dark'   => 'Dark',
        'theme_light' => 'Light',
        'modal_title'  => 'Log out?',
        'modal_body' => 'You\'ll need to log back in.',
        'modal_cancel' => 'Cancel',
        'modal_yes' => 'Log out',
        'chip_1'       => 'dog',
        'chip_2'       => 'learn',
        'chip_3'       => 'language',
        'chip_4'       => 'write',
        'chip_5'       => 'understand',
    ],
][$lang] ?? null;
if (!$t) {
    $lang = 'uk';
    $t = array_values([])[0];
} // fallback handled above
?>
<!DOCTYPE html>
<html lang="<?= $t['html_lang'] ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#060d1a" id="meta-theme">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <?php include 'function/tags/icons.html'; ?>
    <?php include 'function/tags/seo.html'; ?>
    <title><?= htmlspecialchars($t['page_title']) ?></title>
    <link rel="canonical" href="https://wortly.one/?lang=<?= $lang ?>">
    <link rel="alternate" hreflang="uk" href="https://wortly.one/?lang=uk">
    <link rel="alternate" hreflang="de" href="https://wortly.one/?lang=de">
    <link rel="alternate" hreflang="en" href="https://wortly.one/?lang=en">
    <link rel="alternate" hreflang="x-default" href="https://wortly.one/">
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/main-page.css?v=0.0.1">
    <script>
        (function() {
            var t = localStorage.getItem('wortly-theme');
            if (!t) t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
        })();
    </script>
</head>

<body>

    <header id="hdr">
        <div class="hdr-inner">
            <a href="index.php" class="logo" aria-label="Wortly">
                <img src="image/icons/icon-512.png" alt="Wortly" class="logo-img">
                <span class="logo-text">Wortly</span>
                <span class="beta-tag"><?= htmlspecialchars($t['beta']) ?></span>
            </a>
            <div class="hdr-right">
                <div class="lang-sw" role="group" aria-label="Language">
                    <?php foreach (['uk' => 'UA', 'de' => 'DE', 'en' => 'GB'] as $code => $lbl): ?>
                        <a href="?lang=<?= $code ?>" class="lbtn <?= $lang === $code ? 'on' : '' ?>"><?= $lbl ?></a>
                    <?php endforeach; ?>
                </div>
                <nav class="nav-row">
                    <?php if ($is_logged_in): ?>
                        <a href="dashboard.php" class="nbtn ghost"><?= htmlspecialchars($t['nav_back']) ?></a>
                        <button onclick="openLogout()" class="nbtn red"><?= htmlspecialchars($t['nav_logout']) ?></button>
                    <?php else: ?>
                        <a href="login.php" class="nbtn ghost"><?= htmlspecialchars($t['nav_login']) ?></a>
                        <a href="register.php" class="nbtn gold"><?= htmlspecialchars($t['nav_start']) ?></a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Mobile burger (< 450px only) -->
            <button class="burger-btn" id="burgerBtn" aria-label="Menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>

    <!-- Mobile drawer -->
    <div class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
        <div class="lang-sw" role="group" aria-label="Language">
            <?php foreach (['uk' => 'UA', 'de' => 'DE', 'en' => 'GB'] as $code => $lbl): ?>
                <a href="?lang=<?= $code ?>" class="lbtn <?= $lang === $code ? 'on' : '' ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php" class="nbtn ghost"><?= htmlspecialchars($t['nav_back']) ?></a>
            <button onclick="openLogout(); closeDrawer()" class="nbtn red"><?= htmlspecialchars($t['nav_logout']) ?></button>
        <?php else: ?>
            <a href="login.php" class="nbtn ghost"><?= htmlspecialchars($t['nav_login']) ?></a>
            <a href="register.php" class="nbtn gold"><?= htmlspecialchars($t['nav_start']) ?></a>
        <?php endif; ?>
    </div>

    <section class="hero">
        <div class="hero-paper-lines" aria-hidden="true"></div>
        <div class="hero-glow" aria-hidden="true"></div>
        <div class="hero-inner">
            <div class="dict-card reveal">
                <div class="dict-spine"></div>
                <div class="dict-content">
                    <div class="dict-header">
                        <div class="dict-lang-badge">🇩🇪 Deutsch</div>
                        <div class="dict-edition">Wortly Dictionary</div>
                    </div>
                    <div class="dict-entry">
                        <h1 class="dict-headword"><?= htmlspecialchars($t['entry_word']) ?></h1>
                        <div class="dict-meta-row">
                            <span class="dict-phon"><?= htmlspecialchars($t['entry_phon']) ?></span>
                            <span class="dict-pos"><?= htmlspecialchars($t['entry_pos']) ?></span>
                        </div>
                        <hr class="dict-rule">
                        <p class="dict-def"><span class="dict-num">1.</span><?= htmlspecialchars($t['entry_def']) ?></p>
                        <p class="dict-example"><em>«<?= htmlspecialchars($t['entry_ex']) ?>»</em></p>
                        <p class="dict-syn"><?= htmlspecialchars($t['entry_syn']) ?></p>
                    </div>
                    <div class="dict-actions">
                        <?php if ($is_logged_in): ?>
                            <a href="dashboard.php" class="btn-gold"><?= htmlspecialchars($t['cta_dash']) ?></a>
                        <?php else: ?>
                            <a href="register.php" class="btn-gold"><?= htmlspecialchars($t['cta_free']) ?></a>
                            <a href="login.php" class="btn-ghost-sm"><?= htmlspecialchars($t['cta_login']) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="float-chips" aria-hidden="true">
                <div class="chip chip-1">der Hund <span>- <?= htmlspecialchars($t['chip_1']) ?></span></div>
                <div class="chip chip-2">lernen <span>- <?= htmlspecialchars($t['chip_2']) ?></span></div>
                <div class="chip chip-3">die Sprache <span>- <?= htmlspecialchars($t['chip_3']) ?></span></div>
                <div class="chip chip-4">schreiben <span>- <?= htmlspecialchars($t['chip_4']) ?></span></div>
                <div class="chip chip-5">verstehen <span>- <?= htmlspecialchars($t['chip_5']) ?></span></div>
            </div>
        </div>
    </section>

    <div class="stats-strip">
        <div class="ss-inner">
            <div class="ss-item scroll-reveal"><strong><?= number_format($total_words) ?></strong><span><?= htmlspecialchars($t['stat_words']) ?></span></div>
            <div class="ss-div"></div>
            <div class="ss-item scroll-reveal"><strong><?= number_format($total_days) ?></strong><span><?= htmlspecialchars($t['stat_topics']) ?></span></div>
            <div class="ss-div"></div>
            <div class="ss-item scroll-reveal"><strong>100%</strong><span><?= htmlspecialchars($t['stat_free']) ?></span></div>
            <div class="ss-div"></div>
            <div class="ss-item scroll-reveal"><strong>24/7</strong><span><?= htmlspecialchars($t['stat_online']) ?></span></div>
        </div>
    </div>

    <section class="sec why-sec">
        <div class="sec-inner">
            <p class="sec-eyebrow"><?= htmlspecialchars($t['why_label']) ?></p>
            <h2 class="sec-h2"><?= htmlspecialchars($t['why_title']) ?></h2>
            <div class="why-grid">
                <?php foreach ($t['why_cards'] as $i => $c): ?>
                    <div class="why-card <?= $i === 2 ? 'why-yes' : '' ?> scroll-reveal" style="--d:<?= $i * 70 ?>ms">
                        <span class="why-ico" style="display: flex; align-items: flex-start; justify-content: center; margin-right: 16px; padding-top: 2px;">
                            <?php if ($c[0] === '❌'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" style="flex-shrink: 0;">
                                    <rect x="1" y="1" width="22" height="22" rx="6" fill="#fb7185" fill-opacity="0.12" stroke="#fb7185" stroke-opacity="0.25" stroke-width="1.5" />
                                    <path d="M14.5 9.5L9.5 14.5M9.5 9.5L14.5 14.5" stroke="#fb7185" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" style="flex-shrink: 0;">
                                    <rect x="1" y="1" width="22" height="22" rx="6" fill="#4ade80" fill-opacity="0.12" stroke="#4ade80" stroke-opacity="0.25" stroke-width="1.5" />
                                    <path d="M8.5 12.5L10.5 14.5L15.5 9.5" stroke="#4ade80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            <?php endif; ?>
                        </span>
                        <div>
                            <strong><?= htmlspecialchars($c[1]) ?></strong>
                            <p><?= htmlspecialchars($c[2]) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="sec feat-sec">
        <div class="sec-inner">
            <p class="sec-eyebrow"><?= htmlspecialchars($t['feat_label']) ?></p>
            <h2 class="sec-h2"><?= htmlspecialchars($t['feat_title']) ?></h2>
            <div class="feat-grid">
                <?php foreach ($t['features'] as $i => $f): ?>
                    <div class="feat-card scroll-reveal" style="--d:<?= $i * 55 ?>ms">
                        <div class="feat-top">
                            <span class="feat-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="feat-ico"><?= $f[0] ?></span>
                        </div>
                        <h3><?= htmlspecialchars($f[1]) ?></h3>
                        <p><?= htmlspecialchars($f[2]) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="sec how-sec">
        <div class="sec-inner">
            <p class="sec-eyebrow"><?= htmlspecialchars($t['how_label']) ?></p>
            <h2 class="sec-h2"><?= htmlspecialchars($t['how_title']) ?></h2>
            <div class="steps">
                <?php foreach ($t['steps'] as $i => $s): ?>
                    <div class="step scroll-reveal" style="--d:<?= $i * 80 ?>ms">
                        <div class="step-num-wrap">
                            <div class="step-num"><?= $s[0] ?></div>
                            <?php if ($i < count($t['steps']) - 1): ?><div class="step-connector"></div><?php endif; ?>
                        </div>
                        <div class="step-body">
                            <h3><?= htmlspecialchars($s[1]) ?></h3>
                            <p><?= htmlspecialchars($s[2]) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="sec reviews-sec">
        <div class="sec-inner">
            <p class="sec-eyebrow"><?= htmlspecialchars($t['trust_label']) ?></p>
            <h2 class="sec-h2"><?= htmlspecialchars($t['trust_title']) ?></h2>
            <div class="reviews-grid">
                <?php foreach ($t['reviews'] as $r): ?>
                    <div class="review-card scroll-reveal">
                        <div class="review-stars" aria-label="5 stars">★★★★★</div>
                        <p class="review-text"><?= htmlspecialchars($r[0]) ?></p>
                        <p class="review-author">- <?= htmlspecialchars($r[1]) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="cta-final">
        <div class="cta-final-inner scroll-reveal">
            <p class="cta-eyebrow"><?= htmlspecialchars($t['cta_label']) ?></p>
            <h2><?= htmlspecialchars($t['cta_title']) ?></h2>
            <p class="cta-sub"><?= htmlspecialchars($t['cta_sub']) ?></p>
            <?php if (!$is_logged_in): ?>
                <a href="register.php" class="btn-gold lg"><?= htmlspecialchars($t['cta_btn']) ?></a>
            <?php else: ?>
                <a href="dashboard.php" class="btn-gold lg"><?= htmlspecialchars($t['cta_btn_dash']) ?></a>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="footer-inner">
            <span class="footer-logo"></span>
            <div class="footer-links">
                <a href="#"><?= htmlspecialchars($t['f_priv']) ?></a>
                <a href="#"><?= htmlspecialchars($t['f_terms']) ?></a>

                <div class="theme-switch" role="group" aria-label="Theme">
                    <span class="ts-icon" aria-hidden="true">🌙</span>
                    <label class="ts-track" aria-label="Toggle light mode">
                        <input type="checkbox" id="themeToggle" role="switch">
                        <span class="ts-slider"></span>
                    </label>
                    <span class="ts-icon" aria-hidden="true">☀️</span>
                </div>
            </div>
        </div>
    </footer>

    <div id="logoutModal" class="modal-wrap" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal-box">
            <div class="modal-ico">🚪</div>
            <h2><?= htmlspecialchars($t['modal_title']) ?></h2>
            <p><?= htmlspecialchars($t['modal_body']) ?></p>
            <div class="modal-row">
                <button class="mbtn cancel" onclick="closeLogout()"><?= htmlspecialchars($t['modal_cancel']) ?></button>
                <button class="mbtn confirm" onclick="confirmLogout()"><?= htmlspecialchars($t['modal_yes']) ?></button>
            </div>
        </div>
    </div>
    <form id="logoutForm" method="POST" action="profile/logout.php" style="display:none"></form>

    <script>
        /* ── THEME ─────────────────────────────── */
        const html = document.documentElement;
        const toggle = document.getElementById('themeToggle');
        const metaTheme = document.getElementById('meta-theme');

        function applyTheme(theme) {
            if (theme === 'light') {
                html.setAttribute('data-theme', 'light');
                toggle.checked = true;
                metaTheme.setAttribute('content', '#f0ebe0');
            } else {
                html.removeAttribute('data-theme');
                toggle.checked = false;
                metaTheme.setAttribute('content', '#060d1a');
            }
            localStorage.setItem('wortly-theme', theme);
        }

        // init from storage or system preference
        (function() {
            var saved = localStorage.getItem('wortly-theme');
            if (!saved) saved = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            applyTheme(saved);
        })();

        toggle.addEventListener('change', () => applyTheme(toggle.checked ? 'light' : 'dark'));

        // Watch system preference change (if no manual override)
        window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', e => {
            if (!localStorage.getItem('wortly-theme')) applyTheme(e.matches ? 'light' : 'dark');
        });

        /* ── MODAL ─────────────────────────────── */
        const modal = document.getElementById('logoutModal');

        function openLogout() {
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeLogout() {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }

        function confirmLogout() {
            document.getElementById('logoutForm').submit();
        }
        modal.addEventListener('click', e => {
            if (e.target === modal) closeLogout();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeLogout();
        });

        /* ── BURGER MENU ───────────────────────── */
        const burgerBtn = document.getElementById('burgerBtn');
        const mobileDrawer = document.getElementById('mobileDrawer');

        function closeDrawer() {
            burgerBtn.classList.remove('open');
            mobileDrawer.classList.remove('open');
            burgerBtn.setAttribute('aria-expanded', 'false');
            mobileDrawer.setAttribute('aria-hidden', 'true');
        }

        burgerBtn && burgerBtn.addEventListener('click', () => {
            const isOpen = mobileDrawer.classList.toggle('open');
            burgerBtn.classList.toggle('open', isOpen);
            burgerBtn.setAttribute('aria-expanded', String(isOpen));
            mobileDrawer.setAttribute('aria-hidden', String(!isOpen));
        });

        // Close drawer on outside click
        document.addEventListener('click', e => {
            if (mobileDrawer && mobileDrawer.classList.contains('open') &&
                !mobileDrawer.contains(e.target) && !burgerBtn.contains(e.target)) {
                closeDrawer();
            }
        });

        /* ── HEADER SHADOW ─────────────────────── */
        const hdr = document.getElementById('hdr');
        window.addEventListener('scroll', () => hdr.classList.toggle('scrolled', window.scrollY > 8), {
            passive: true
        });

        /* ── SCROLL REVEAL ─────────────────────── */
        const io = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('in');
                    io.unobserve(e.target);
                }
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px 0px -32px 0px'
        });
        document.querySelectorAll('.scroll-reveal, .reveal').forEach(el => io.observe(el));
    </script>
</body>

</html>