<?php
session_start();
require_once '../config.php';

$id = (int)($_GET['id'] ?? 0);

// Получаем модуль
$stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
$stmt->execute([$id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$module) die("Модуль не знайдено");

// Слова модуля
$stmt = $pdo->prepare("SELECT * FROM module_words WHERE module_id = ? ORDER BY id DESC");
$stmt->execute([$id]);
$words = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<title>Редагування модуля: <?= htmlspecialchars($module['title']) ?></title>
<style>
table, th, td { border: 1px solid #ccc; border-collapse: collapse; padding: 5px; }
</style>
</head>
<body>
<h1>Редагування модуля: <?= htmlspecialchars($module['title']) ?></h1>

<h2>Додати слово вручну</h2>
<form method="POST" action="modules_word_add.php">
    <input type="hidden" name="module_id" value="<?= $id ?>">
    <input name="article" placeholder="Артикль"><br>
    <input name="german" placeholder="Німецьке слово" required><br>
    <input name="translation" placeholder="Переклад" required><br>
    <select name="type">
        <option value="noun">іменник</option>
        <option value="verb">дієслово</option>
        <option value="adj">прикметник</option>
    </select><br>
    <button>Додати</button>
</form>

<h2>Слова в модулі</h2>
<table>
<tr><th>Артикль</th><th>Слово</th><th>Переклад</th><th>Тип</th></tr>
<?php foreach ($words as $w): ?>
<tr>
<td><?= htmlspecialchars($w['article']) ?></td>
<td><?= htmlspecialchars($w['german']) ?></td>
<td><?= htmlspecialchars($w['translation']) ?></td>
<td><?= htmlspecialchars($w['type']) ?></td>
</tr>
<?php endforeach; ?>
</table>

<h2>Швидкий імпорт JSON</h2>
<p>Завантажте JSON-файл з масивом слів у форматі:</p>
<pre>
[
  {"article":"der","german":"Apfel","translation":"яблуко","type":"noun"},
  {"article":"die","german":"Banane","translation":"банан","type":"noun"}
]
</pre>

<input type="file" id="jsonFile">
<button id="previewBtn">Переглянути слова</button>

<h3>Попередній перегляд:</h3>
<table id="previewTable">
<tr><th>Артикль</th><th>Слово</th><th>Переклад</th><th>Тип</th></tr>
</table>

<button id="importBtn" style="display:none;">Додати всі слова у модуль</button>

<script>
let importedWords = [];

document.getElementById('previewBtn').addEventListener('click', function() {
    const fileInput = document.getElementById('jsonFile');
    if (!fileInput.files.length) return alert('Виберіть файл JSON');

    const reader = new FileReader();
    reader.onload = function() {
        try {
            importedWords = JSON.parse(reader.result);
        } catch (e) {
            return alert('Помилка парсингу JSON');
        }

        const table = document.getElementById('previewTable');
        table.innerHTML = '<tr><th>Артикль</th><th>Слово</th><th>Переклад</th><th>Тип</th></tr>';

        importedWords.forEach(w => {
            const row = table.insertRow();
            row.insertCell(0).textContent = w.article || '';
            row.insertCell(1).textContent = w.german || '';
            row.insertCell(2).textContent = w.translation || '';
            row.insertCell(3).textContent = w.type || '';
        });

        document.getElementById('importBtn').style.display = 'inline-block';
    };
    reader.readAsText(fileInput.files[0]);
});

document.getElementById('importBtn').addEventListener('click', function() {
    if (!importedWords.length) return;
    fetch('modules_import_json.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({module_id: <?= $id ?>, words: importedWords})
    })
    .then(res => res.text())
    .then(resp => {
        alert(resp);
        location.reload();
    });
});
</script>

</body>
</html>
