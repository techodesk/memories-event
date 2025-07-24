<?php
session_start();
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Translation.php';

$slug = $_GET['slug'] ?? '';

$code = '';
if (isset($_SERVER['PATH_INFO'])) {
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    $code = $parts[1] ?? '';
} elseif (isset($_GET['code'])) {
    $code = $_GET['code'];
} elseif (isset($_GET['invite'])) {
    $code = $_GET['invite'];
}

function show_error(string $msg): void {
    echo '<div style="color:#c00;font-size:1.3em;text-align:center;margin-top:100px;">'.htmlspecialchars($msg).'</div>';
    exit;
}

if ($slug === '' || $code === '') {
    show_error('Missing link or invite code');
}

$memDbConf = $config['db_memories'];
$memPdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$emDbConf = $config['db_event_manager'];
$emPdo = new PDO(
    "mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}",
    $emDbConf['user'],
    $emDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$newsStmt = $memPdo->prepare('SELECT * FROM news WHERE slug=?');
$newsStmt->execute([$slug]);
$news = $newsStmt->fetch(PDO::FETCH_ASSOC);
if (!$news) {
    show_error('News not found');
}

$guestStmt = $emPdo->prepare('SELECT id FROM guests WHERE invite_code=? AND rsvp_status="Accepted"');
$guestStmt->execute([$code]);
$guestId = $guestStmt->fetchColumn();
if (!$guestId) {
    show_error('Invalid invite code');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'newsopened') {
    $stmt = $memPdo->prepare('INSERT INTO news_reads (news_id, guest_id) VALUES (?, ?)');
    $stmt->execute([$news['id'], $guestId]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

$tr = new Translation();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($news['title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{background:#fcfaf7;margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;}
        .content{margin:0;padding:0;}
        img{width:100%;height:auto;display:block;margin-bottom:0;}
    </style>
</head>
<body>
<div class="content">
    <?php
    $images = [];
    if (!empty($news['image_urls'])) {
        $images = json_decode($news['image_urls'], true) ?: [];
    }
    if (empty($images) && !empty($news['image_url'])) {
        $images[] = $news['image_url'];
    }
    foreach ($images as $img): ?>
        <img src="<?= htmlspecialchars($img) ?>" alt="">
    <?php endforeach; ?>
    <h1><?= htmlspecialchars($news['title']) ?></h1>
    <div><?= nl2br(htmlspecialchars($news['content'])) ?></div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!sessionStorage.getItem('newsOpened')) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({mode: 'newsopened'})
            });
            sessionStorage.setItem('newsOpened', '1');
        }
    });
</script>
</body>
</html>
