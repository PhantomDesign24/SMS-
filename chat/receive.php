<?php
require_once(__DIR__.'/db_config.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['nickname'], $data['message'])) {
    http_response_code(400);
    exit('Invalid data');
}

$pdo = getDB();
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare("INSERT INTO chat_log (nickname, message, source, created_at) VALUES (?, ?, 'discord', ?)");
$stmt->execute([$data['nickname'], $data['message'], $now]);

echo "ok";
?>
