<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sms');
define('DB_USER', 'sms');
define('DB_PASS', 'T76.I/VXB1GFEyzk');

function getDB() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->exec("SET time_zone = '+09:00'");
        return $pdo;
    } catch (PDOException $e) {
        die('DB 연결 오류: ' . $e->getMessage());
    }
}
?>
