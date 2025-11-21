<?php
function getAppVersion(PDO $pdo): string {
    $stmt = $pdo->query("SELECT app_version FROM app_metadata ORDER BY id DESC LIMIT 1");
    return $stmt->fetchColumn() ?: '1.0.0';
}
?>