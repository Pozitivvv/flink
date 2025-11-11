<?php
session_start();
require_once '../config.php';

$stmt = $pdo->query("SELECT * FROM modules ORDER BY id DESC");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<body>
<h1>Модулі</h1>
<a href="modules_create.php">➕ Новий модуль</a>
<table border="1">
<tr><th>ID</th><th>Назва</th><th>Дії</th></tr>
<?php foreach ($modules as $m): ?>
<tr>
<td><?= $m['id'] ?></td>
<td><?= $m['title'] ?></td>
<td>
    <a href="modules_edit.php?id=<?= $m['id'] ?>">Редагувати</a>
</td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>