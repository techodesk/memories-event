<?php
session_set_cookie_params(12 * 3600);
session_start();

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/memories/UploadManager.php';

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
$eventId = $event['id'] ?? 0;

// --- Handle new post ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    if (isset($_FILES['media']) && is_uploaded_file($_FILES['media']['tmp_name'])) {
        $uploader = new UploadManager($config['do_spaces']);
        $fileUrl = $uploader->upload($_FILES['media']['tmp_name'], $_FILES['media']['name']);
        $stmt = $memPdo->prepare(
            'INSERT INTO event_posts (event_id, session_id, file_url, caption) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$eventId, $sessionId, $fileUrl, $_POST['caption'] ?? null]);
    }
    header('Location: event_public.php?public_id=' . urlencode($publicId));
    exit;
}

// --- Handle comment ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_comment'])) {
    $stmt = $memPdo->prepare('INSERT INTO post_comments (post_id, session_id, content) VALUES (?, ?, ?)');
    $stmt->execute([intval($_POST['post_id']), $sessionId, trim($_POST['comment'])]);
    header('Location: event_public.php?public_id=' . urlencode($publicId));
    exit;
}

// --- Handle like toggle ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    $postId = intval($_POST['post_id']);
    $check = $memPdo->prepare('SELECT id FROM post_likes WHERE post_id=? AND session_id=?');
    $check->execute([$postId, $sessionId]);
    if ($likeId = $check->fetchColumn()) {
        $memPdo->prepare('DELETE FROM post_likes WHERE id=?')->execute([$likeId]);
    } else {
        $memPdo->prepare('INSERT INTO post_likes (post_id, session_id) VALUES (?, ?)')->execute([$postId, $sessionId]);
    }
    header('Location: event_public.php?public_id=' . urlencode($publicId));
    exit;
}

// --- Handle delete post ---
if (isset($_GET['delete_post'])) {
    $pid = intval($_GET['delete_post']);
    $stmt = $memPdo->prepare('DELETE FROM event_posts WHERE id=? AND session_id=?');
    $stmt->execute([$pid, $sessionId]);
    $memPdo->prepare('DELETE FROM post_comments WHERE post_id=?')->execute([$pid]);
    $memPdo->prepare('DELETE FROM post_likes WHERE post_id=?')->execute([$pid]);
    header('Location: event_public.php?public_id=' . urlencode($publicId));
    exit;
}

function isVideo(string $url): bool
{
    $path = parse_url($url, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $video = ['mp4', 'mov', 'webm', 'mkv', 'avi', 'flv', 'wmv', '3gp', 'mpeg', 'mpg'];
    return in_array($ext, $video, true);
}

// --- Fetch posts with comments and likes ---
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

if (!$event) {
    http_response_code(404);
    echo 'Event not found';
    exit;
}

$page_title = $event['event_name'];
include __DIR__ . '/../templates/header.php';
?>
<?php if (!empty($event['custom_css'])): ?>
    <style><?= $event['custom_css'] ?></style>
<?php endif; ?>
<main class="d-flex justify-content-center" style="min-height:100vh;">
    <div class="p-4" style="background: var(--card-bg); border-radius: var(--border-radius); max-width:720px; width:100%;">
        <?php if (!empty($event['header_image'])): ?>
            <img src="<?= htmlspecialchars($event['header_image']) ?>" class="img-fluid mb-3" alt="header" style="border-radius:var(--border-radius);">
        <?php endif; ?>
        <h1 class="mb-3 text-center"><?= htmlspecialchars($event['event_name']) ?></h1>
        <p><strong>Date:</strong> <?= htmlspecialchars($event['event_date']) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($event['event_location']) ?></p>
        <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>

        <hr class="my-4">
        <h4 class="mb-3">Share a Memory</h4>
        <form method="post" enctype="multipart/form-data" class="mb-4">
            <input type="hidden" name="new_post" value="1">
            <div class="mb-3">
                <input type="file" name="media" id="mediaInput" class="form-control" accept="image/*,video/*" required>
            </div>
            <div id="preview" class="mb-3"></div>
            <div class="mb-3">
                <textarea name="caption" class="form-control" rows="2" placeholder="Say something..."></textarea>
            </div>
            <button type="submit" class="btn btn-accent">Upload</button>
        </form>

        <h4 class="mb-3">Memories</h4>
        <?php foreach ($posts as $p): ?>
            <div class="card mb-3" style="background: var(--sidebar-bg);">
                <div class="card-body">
                    <?php if (isVideo($p['file_url'])): ?>
                        <video src="<?= htmlspecialchars($p['file_url']) ?>" class="img-fluid mb-2" controls></video>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($p['file_url']) ?>" class="img-fluid mb-2" alt="post">
                    <?php endif; ?>
                    <?php if (!empty($p['caption'])): ?>
                        <p><?= htmlspecialchars($p['caption']) ?></p>
                    <?php endif; ?>
                    <div class="d-flex align-items-center mb-2">
                        <form method="post" class="me-2">
                            <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                            <button name="like_post" class="btn btn-sm btn-outline-secondary" type="submit">
                                <?= $p['liked'] ? 'Unlike' : 'Like' ?> (<?= $p['likes'] ?>)
                            </button>
                        </form>
                        <?php if ($p['session_id'] === $sessionId): ?>
                            <a href="?public_id=<?= urlencode($publicId) ?>&delete_post=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this post?')">Delete</a>
                        <?php endif; ?>
                    </div>
                    <?php foreach ($p['comments'] as $c): ?>
                        <div class="border rounded p-2 mb-2" style="background: var(--card-bg);">
                            <?= htmlspecialchars($c['content']) ?>
                        </div>
                    <?php endforeach; ?>
                    <form method="post" class="mt-2">
                        <input type="hidden" name="new_comment" value="1">
                        <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                        <div class="input-group">
                            <input type="text" name="comment" class="form-control" placeholder="Add a comment" required>
                            <button class="btn btn-secondary" type="submit">Post</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<script>
document.getElementById('mediaInput')?.addEventListener('change', function() {
    const preview = document.getElementById('preview');
    preview.innerHTML = '';
    const file = this.files[0];
    if (!file) return;
    if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.className = 'img-fluid mb-2';
        preview.appendChild(img);
    } else if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.className = 'img-fluid mb-2';
        video.controls = true;
        preview.appendChild(video);
    }
});
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
