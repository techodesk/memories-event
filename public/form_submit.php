<?php
$config = require __DIR__ . '/../config/config.php';
$memDbConf = $config['db_memories'];
$pdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare('SELECT id FROM forms WHERE slug=?');
$stmt->execute([$slug]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    http_response_code(404);
    exit('Not found');
}
$data = json_encode($_POST);
$ins = $pdo->prepare('INSERT INTO form_submissions (form_id, data) VALUES (?, ?)');
$ins->execute([$form['id'], $data]);
echo 'OK';

