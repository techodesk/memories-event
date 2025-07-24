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
$code = $_GET['code'] ?? ($_GET['invite'] ?? '');
$guestId = null;
if ($code !== '') {
    $emDbConf = $config['db_event_manager'];
    $emPdo = new PDO(
        "mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}",
        $emDbConf['user'],
        $emDbConf['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $gStmt = $emPdo->prepare('SELECT id FROM guests WHERE invite_code=? AND rsvp_status="Accepted"');
    $gStmt->execute([$code]);
    $guestId = $gStmt->fetchColumn() ?: null;
}
$data = json_encode($_POST);
$ins = $pdo->prepare('INSERT INTO form_submissions (form_id, guest_id, data) VALUES (?, ?, ?)');
$ins->execute([$form['id'], $guestId, $data]);
echo 'OK';

