<?php
session_set_cookie_params(12 * 3600);
session_start();

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/memories/UploadManager.php';
require_once __DIR__ . '/../src/memories/MediaProcessor.php';
require_once __DIR__ . '/../src/Translation.php';

$tr = new Translation();

$sessionId = $_SESSION['guest_session'] ?? null;
if (!$sessionId) {
    $sessionId = bin2hex(random_bytes(16));
    $_SESSION['guest_session'] = $sessionId;
}

$publicId = $_GET['public_id'] ?? '';
if ($publicId === '') {
    http_response_code(404);
    echo 'Event not found';
    exit;
}

function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function redirectSelf(): void
{
    header('Location: ' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    exit;
}

$memDbConf = $config['db_memories'];
$memPdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $memPdo->prepare('SELECT * FROM events WHERE public_id = ? LIMIT 1');
$stmt->execute([$publicId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    http_response_code(404);
    echo 'Event not found';
    exit;
}
$eventId = $event['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $uploadOk = false;
    if (isset($_FILES['media']) && is_uploaded_file($_FILES['media']['tmp_name'])) {
        $uploader = new UploadManager($config['do_spaces']);
        $processor = new MediaProcessor($uploader, dirname(__DIR__) . '/uploads');
        $fileUrl = $processor->processAndUpload(
            $eventId,
            $event['upload_folder'] ?? '',
            $_FILES['media'],
            $sessionId
        );
        if ($fileUrl) {
            $stmt = $memPdo->prepare(
                'INSERT INTO event_posts (event_id, session_id, file_url, caption) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$eventId, $sessionId, $fileUrl, $_POST['caption'] ?? null]);
            $uploadOk = true;
        }
    }
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => $uploadOk]);
        exit;
    }
    redirectSelf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_comment'])) {
    $stmt = $memPdo->prepare('INSERT INTO post_comments (post_id, session_id, content) VALUES (?, ?, ?)');
    $stmt->execute([intval($_POST['post_id']), $sessionId, trim($_POST['comment'])]);
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['comment' => htmlspecialchars(trim($_POST['comment']), ENT_QUOTES)]);
        exit;
    }
    redirectSelf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    $postId = intval($_POST['post_id']);
    $check = $memPdo->prepare('SELECT id FROM post_likes WHERE post_id=? AND session_id=?');
    $check->execute([$postId, $sessionId]);
    if ($likeId = $check->fetchColumn()) {
        $memPdo->prepare('DELETE FROM post_likes WHERE id=?')->execute([$likeId]);
    } else {
        $memPdo->prepare('INSERT INTO post_likes (post_id, session_id) VALUES (?, ?)')->execute([$postId, $sessionId]);
    }
    $countStmt = $memPdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id=?');
    $countStmt->execute([$postId]);
    $likes = (int)$countStmt->fetchColumn();
    $check = $memPdo->prepare('SELECT id FROM post_likes WHERE post_id=? AND session_id=?');
    $check->execute([$postId, $sessionId]);
    $liked = (bool)$check->fetchColumn();
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['liked' => $liked, 'likes' => $likes]);
        exit;
    }
    redirectSelf();
}

if (isset($_GET['delete_post'])) {
    $pid = intval($_GET['delete_post']);
    $stmt = $memPdo->prepare('DELETE FROM event_posts WHERE id=? AND session_id=?');
    $stmt->execute([$pid, $sessionId]);
    $memPdo->prepare('DELETE FROM post_comments WHERE post_id=?')->execute([$pid]);
    $memPdo->prepare('DELETE FROM post_likes WHERE post_id=?')->execute([$pid]);
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
    redirectSelf();
}

function isVideo(string $url): bool
{
    $path = parse_url($url, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'mov', 'webm', 'mkv', 'avi', 'flv', 'wmv', '3gp', 'mpeg', 'mpg'], true);
}

$postsStmt = $memPdo->prepare('SELECT * FROM event_posts WHERE event_id=? ORDER BY created_at DESC');
$postsStmt->execute([$eventId]);
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($posts as &$p) {
    $cStmt = $memPdo->prepare('SELECT * FROM post_comments WHERE post_id=? ORDER BY created_at');
    $cStmt->execute([$p['id']]);
    $p['comments'] = $cStmt->fetchAll(PDO::FETCH_ASSOC);
    $countStmt = $memPdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id=?');
    $countStmt->execute([$p['id']]);
    $p['likes'] = (int)$countStmt->fetchColumn();
    $check = $memPdo->prepare('SELECT id FROM post_likes WHERE post_id=? AND session_id=?');
    $check->execute([$p['id'], $sessionId]);
    $p['liked'] = (bool)$check->fetchColumn();
}
unset($p);

include __DIR__ . '/views/event_public_redesign.php';
