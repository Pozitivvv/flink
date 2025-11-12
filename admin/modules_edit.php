<?php
// admin/modules_edit.php
session_start();
require_once '../config.php';
require_once 'config.php';

check_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    die("❌ ID модуля не указан. <a href='?page=modules'>← Вернуться к модулям</a>");
}

$message = '';

// Получаем модуль
$stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->execute([$id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    die("❌ Модуль не найден. <a href='?page=modules'>← Вернуться к модулям</a>");
}

// Слова модуля
$stmt = $pdo->prepare("SELECT * FROM module_words WHERE module_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔧 Добавление слова вручную
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_word') {
    try {
        $article = trim($_POST['article'] ?? '');
        $german = trim($_POST['german'] ?? '');
        $translation = trim($_POST['translation'] ?? '');
        $type = trim($_POST['type'] ?? 'noun');

        if (empty($german) || empty($translation)) {
            throw new Exception("Слово и перевод обязательны");
        }

        $stmt = $pdo->prepare("INSERT INTO module_words (module_id, article, german, translation, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $article, $german, $translation, $type]);

        $message = '<div class="message success">✅ Слово успешно добавлено!</div>';

        $stmt = $pdo->prepare("SELECT * FROM module_words WHERE module_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $message = '<div class="message error">❌ ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// 🗑️ Удаление слова
if (isset($_GET['delete_word'])) {
    $word_id = (int)$_GET['delete_word'];
    $stmt = $pdo->prepare("DELETE FROM module_words WHERE id = ? AND module_id = ?");
    $stmt->execute([$word_id, $id]);
    header("Location: ?page=edit_module&id=$id");
    exit;
}

// ✏️ Редактирование слова
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_word') {
    try {
        $word_id = (int)$_POST['word_id'];
        $article = trim($_POST['article'] ?? '');
        $german = trim($_POST['german'] ?? '');
        $translation = trim($_POST['translation'] ?? '');
        $type = trim($_POST['type'] ?? 'noun');

        if (empty($german) || empty($translation)) {
            throw new Exception("Слово и перевод обязательны");
        }

        $stmt = $pdo->prepare("UPDATE module_words SET article = ?, german = ?, translation = ?, type = ? WHERE id = ? AND module_id = ?");
        $stmt->execute([$article, $german, $translation, $type, $word_id, $id]);

        $message = '<div class="message success">✅ Слово успешно обновлено!</div>';

        $stmt = $pdo->prepare("SELECT * FROM module_words WHERE module_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $message = '<div class="message error">❌ ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Редагування модуля: <?= htmlspecialchars($module['title']) ?></title>
    <link rel="stylesheet" href="assets/main-style.css">
    <link rel="stylesheet" href="assets/modules-edit.css">
</head>
<body>
    <div class="container">
        <!-- GREETING/HEADER -->
        <div class="greeting">
            <div class="greeting-content">
                <div class="greeting-icon">📝</div>
                <div class="greeting-text">
                    <h1><?= htmlspecialchars($module['title']) ?></h1>
                    <p>Управління словами</p>
                </div>
            </div>
            <button class="back-link" onclick="window.history.back()">← Назад</button>
        </div>

        <!-- MESSAGE -->
        <?= $message ?>

        <!-- TABS -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab(event, 'words')">📚 Слова (<?= count($words) ?>)</button>
            <button class="tab-btn" onclick="switchTab(event, 'add')">➕ Додати</button>
            <button class="tab-btn" onclick="switchTab(event, 'import')">📤 Імпорт</button>
        </div>

        <!-- TAB: WORDS -->
        <div id="words" class="tab-content active">
            <div class="card">
                <h2>📚 Усі слова (<?= count($words) ?>)</h2>
                <?php if (count($words) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <tr>
                                <th>Артикль</th>
                                <th>Німецька</th>
                                <th>Переклад</th>
                                <th>Тип</th>
                                <th>Дії</th>
                            </tr>
                            <?php foreach ($words as $w): ?>
                                <tr>
                                    <td><?= htmlspecialchars($w['article'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($w['german']) ?></td>
                                    <td><?= htmlspecialchars($w['translation']) ?></td>
                                    <td><?= htmlspecialchars($w['type']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-small btn-edit" onclick="editWord(<?= $w['id'] ?>, '<?= htmlspecialchars($w['article'], ENT_QUOTES) ?>', '<?= htmlspecialchars($w['german'], ENT_QUOTES) ?>', '<?= htmlspecialchars($w['translation'], ENT_QUOTES) ?>', '<?= htmlspecialchars($w['type'], ENT_QUOTES) ?>')">✏️</button>
                                            <a href="?page=edit_module&id=<?= $id ?>&delete_word=<?= $w['id'] ?>" class="btn-small btn-delete" onclick="return confirm('Видалити?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="empty-state-text">Слова не додані</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: ADD -->
        <div id="add" class="tab-content">
            <div class="card">
                <h2>➕ Додати слово</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_word">
                    <div class="form-group">
                        <label class="form-label">Артикль</label>
                        <select name="article">
                            <option value="">---</option>
                            <option value="der">der</option>
                            <option value="die">die</option>
                            <option value="das">das</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Тип</label>
                        <select name="type">
                            <option value="noun">📖 Іменник</option>
                            <option value="verb">🔄 Дієслово</option>
                            <option value="adj">✨ Прикметник</option>
                            <option value="adv">➡️ Прислівник</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">🇩🇪 Німецька мова <span class="required">*</span></label>
                        <input type="text" name="german" class="form-input" placeholder="Введіть слово" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">🇺🇦 Переклад <span class="required">*</span></label>
                        <input type="text" name="translation" class="form-input" placeholder="Введіть переклад" required>
                    </div>
                    <button type="submit" class="btn-submit">✅ Додати слово</button>
                </form>
            </div>
        </div>

        <!-- TAB: IMPORT -->
        <div id="import" class="tab-content">
            <div class="card">
                <h2>📤 Імпорт JSON</h2>
                <div class="import-options">
                    <button type="button" class="active" onclick="switchImportMode(event, 'file')">📁 Файл</button>
                    <button type="button" onclick="switchImportMode(event, 'text')">📝 Текст</button>
                </div>

                <div id="fileImport" class="form-group">
                    <label class="form-label">Виберіть JSON-файл</label>
                    <input type="file" id="jsonFile" accept=".json" style="display: none;">
                    <div class="file-upload-btn" onclick="document.getElementById('jsonFile').click()">
                        <div class="file-upload-icon">📁</div>
                        <div class="file-upload-text">Натисніть для вибору</div>
                        <div class="file-upload-subtext">або перетягніть файл</div>
                    </div>
                </div>

                <div id="textImport" class="form-group" style="display: none;">
                    <label class="form-label">Вставте JSON</label>
                    <textarea id="jsonText" class="form-textarea" placeholder='[{"article":"der","german":"Apfel","translation":"яблуко","type":"noun"}]'></textarea>
                </div>

                <p style="color: #666; font-size: 13px; margin-top: 12px;">
                    <strong>Формат:</strong><br>
                    <code style="background: #0f0f0f; padding: 8px; display: block; border-radius: 4px; margin-top: 8px; font-size: 11px;">
[{"article":"der","german":"Apfel","translation":"яблуко","type":"noun"}]
                    </code>
                </p>
            </div>

            <div class="card">
                <h2>👁️ Попередній перегляд</h2>
                <div class="table-wrapper">
                    <table id="previewTable">
                        <tr>
                            <th>Артикль</th>
                            <th>Німецька</th>
                            <th>Переклад</th>
                            <th>Тип</th>
                        </tr>
                    </table>
                </div>
                <button id="importBtn" type="button" onclick="importJSON(<?= $id ?>)" class="btn-submit" style="margin-top: 12px;">✅ Імпортувати</button>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Редагувати слово</h3>
                <button class="close-btn" type="button" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_word">
                <input type="hidden" name="word_id" id="word_id">
                <div class="form-group">
                    <label class="form-label">Артикль</label>
                    <select name="article" id="edit_article">
                        <option value="">---</option>
                        <option value="der">der</option>
                        <option value="die">die</option>
                        <option value="das">das</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Тип</label>
                    <select name="type" id="edit_type">
                        <option value="noun">Іменник</option>
                        <option value="verb">Дієслово</option>
                        <option value="adj">Прикметник</option>
                        <option value="adv">Прислівник</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Німецька мова</label>
                    <input type="text" name="german" id="edit_german" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Переклад</label>
                    <input type="text" name="translation" id="edit_translation" class="form-input" required>
                </div>
                <button type="submit" class="btn-submit">💾 Зберегти</button>
            </form>
        </div>
    </div>

    <script>
        let importedWords = [];

        function switchTab(e, tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            e.target.classList.add('active');
        }

        function switchImportMode(e, mode) {
            document.querySelectorAll('.import-options button').forEach(btn => btn.classList.remove('active'));
            e.target.classList.add('active');
            document.getElementById('fileImport').style.display = mode === 'file' ? 'block' : 'none';
            document.getElementById('textImport').style.display = mode === 'text' ? 'block' : 'none';
        }

        function editWord(id, article, german, translation, type) {
            document.getElementById('word_id').value = id;
            document.getElementById('edit_article').value = article;
            document.getElementById('edit_german').value = german;
            document.getElementById('edit_translation').value = translation;
            document.getElementById('edit_type').value = type;
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function previewJSONFromFile() {
            const file = document.getElementById('jsonFile').files[0];
            if (!file) return alert('Виберіть файл');

            const reader = new FileReader();
            reader.onload = function() {
                try {
                    parseAndPreview(reader.result);
                } catch (err) {
                    alert('❌ Помилка JSON: ' + err.message);
                }
            };
            reader.readAsText(file);
        }

        function previewJSONFromText() {
            const text = document.getElementById('jsonText').value.trim();
            if (!text) return alert('Введіть JSON');

            try {
                parseAndPreview(text);
            } catch (err) {
                alert('❌ Помилка JSON: ' + err.message);
            }
        }

        function parseAndPreview(jsonString) {
            importedWords = JSON.parse(jsonString);
            if (!Array.isArray(importedWords)) throw new Error('Повинен бути масив');

            const table = document.getElementById('previewTable');
            table.innerHTML = '<tr><th>Артикль</th><th>Німецька</th><th>Переклад</th><th>Тип</th></tr>';

            importedWords.forEach(w => {
                const row = table.insertRow();
                row.insertCell(0).textContent = w.article || '';
                row.insertCell(1).textContent = w.german || '';
                row.insertCell(2).textContent = w.translation || '';
                row.insertCell(3).textContent = w.type || '';
            });

            document.getElementById('importBtn').style.display = 'block';
        }

        function importJSON(moduleId) {
            fetch('modules_import_json.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({module_id: moduleId, words: importedWords})
            })
            .then(res => res.text())
            .then(resp => {
                alert(resp);
                location.reload();
            })
            .catch(err => alert('❌ Помилка: ' + err));
        }

        document.getElementById('jsonFile')?.addEventListener('change', previewJSONFromFile);

        window.onclick = function(e) {
            const modal = document.getElementById('editModal');
            if (e.target === modal) modal.classList.remove('active');
        }
    </script>
</body>
</html>