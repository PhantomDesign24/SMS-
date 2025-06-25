<?php
require_once(__DIR__.'/db_config.php');

$nickname = $_POST['nickname'] ?? '';
$message = $_POST['message'] ?? '';

if (!$nickname || !$message) exit('Invalid');

$pdo = getDB();
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare("INSERT INTO chat_log (nickname, message, source, created_at) VALUES (?, ?, 'web', ?)");
$stmt->execute([$nickname, $message, $now]);

// 디스코드 웹훅 호출
$webhookUrl = 'https://discord.com/api/webhooks/1387358258741776474/R_NQtaEJaQrePh6r6h3d_L45J0Co-cogfzeXr4_03UTd0iGw4Ya3KiB5K4i5-3xLlWPe';

$data = ['content' => "[$nickname] $message"];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];
$context = stream_context_create($options);
file_get_contents($webhookUrl, false, $context);

echo "ok";
?>
