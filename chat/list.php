<?php
require_once(__DIR__.'/db_config.php');

$pdo = getDB();
$stmt = $pdo->query("SELECT * FROM chat_log ORDER BY id DESC LIMIT 50");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array_reverse($rows));
?>
