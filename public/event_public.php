<?php
session_set_cookie_params(12 * 3600);
session_start();

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/memories/UploadManager.php';
require_once __DIR__ . '/../src/memories/MediaProcessor.php';

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
$eventId = $event['id'] ?? 0;

// --- Handle new post ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $uploadOk = false;
    if (isset($_FILES['media']) && is_uploaded_file($_FILES['media']['tmp_name'])) {
        $uploader = new UploadManager($config['do_spaces']);
        $processor = new MediaProcessor($uploader, __DIR__ . '/../uploads');
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

// --- Handle comment ---
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

// --- Handle delete post ---
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($event['event_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(180deg, #d3f2ff, #ffffff);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 0;
    }
    .event-card {
      border-radius: 24px;
      margin: 20px auto;
      max-width: 480px;
      overflow: hidden;
    }
    .glass {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 24px;
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .event-body { padding: 20px; }
    .event-title {
      font-size: 1.8rem;
      font-weight: bold;
      text-align: center;
      color: #000;
    }
    .event-info {
      text-align: center;
      color: #333;
      margin-bottom: 20px;
    }
    .rsvp-buttons .btn {
      border-radius: 20px;
      width: 30%;
      margin: 0 5px;
    }
    .upload-form, .memory-card, .host-box, .weather-box, .map-box {
      border-radius: 16px;
      padding: 16px;
      margin-bottom: 16px;
    }
    .memory-card img, .memory-card video {
      max-width: 100%;
      border-radius: 12px;
    }
  </style>
</head>
<body>
  <div class="event-card glass">
    <div class="event-cover">
      <img src="<?= htmlspecialchars($event['header_image']) ?>" alt="Event Header" class="img-fluid">
    </div>
    <div class="event-body">
      <div class="event-title"><?= htmlspecialchars($event['event_name']) ?></div>
      <div class="event-info">
        <?= htmlspecialchars($event['event_date']) ?><br>
        <?= htmlspecialchars($event['event_location']) ?>
      </div>
      <div class="text-center mb-3 rsvp-buttons">
        <button class="btn btn-success">Going</button>
        <button class="btn btn-outline-secondary">Not Going</button>
        <button class="btn btn-outline-secondary">Maybe</button>
      </div>

      <div class="host-box glass">
        <strong>Hosted by <?= htmlspecialchars($event['host_name'] ?? 'Someone') ?></strong>
        <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
      </div>

      <form method="post" enctype="multipart/form-data" id="postForm" class="upload-form glass">
        <input type="hidden" name="new_post" value="1">
        <input type="file" name="media" id="mediaInput" class="form-control mb-2" accept="image/*,video/*" required>
        <div id="preview" class="mb-2"></div>
        <textarea name="caption" class="form-control mb-2" placeholder="Say something..."></textarea>
        <button type="submit" class="btn btn-primary w-100">Upload</button>
      </form>

      <h5 class="mt-4 mb-3">Memories</h5>
      <?php foreach ($posts as $p): ?>
      <div class="memory-card glass">
        <?php if (isVideo($p['file_url'])): ?>
          <video src="<?= htmlspecialchars($p['file_url']) ?>" controls></video>
        <?php else: ?>
          <img src="<?= htmlspecialchars($p['file_url']) ?>" alt="Memory">
        <?php endif; ?>
        <?php if (!empty($p['caption'])): ?><p><?= htmlspecialchars($p['caption']) ?></p><?php endif; ?>
        <form method="post" class="like-form d-inline">
          <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
          <button name="like_post" class="btn btn-sm btn-outline-primary" type="submit">
            <?= $p['liked'] ? 'Unlike' : 'Like' ?> (<?= $p['likes'] ?>)
          </button>
        </form>
        <?php if ($p['session_id'] === $sessionId): ?>
          <a href="?public_id=<?= urlencode($publicId) ?>&delete_post=<?= $p['id'] ?>" class="btn btn-sm btn-danger ms-2">Delete</a>
        <?php endif; ?>
        <?php foreach ($p['comments'] as $c): ?>
          <div class="mt-2 p-2 bg-light rounded"> <?= htmlspecialchars($c['content']) ?> </div>
        <?php endforeach; ?>
        <form method="post" class="mt-2 comment-form">
          <input type="hidden" name="new_comment" value="1">
          <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
          <div class="input-group">
            <input type="text" name="comment" class="form-control" placeholder="Add a comment" required>
            <button class="btn btn-outline-secondary" type="submit">Post</button>
          </div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
<script>
document.getElementById('mediaInput')?.addEventListener('change', function() {
  const preview = document.getElementById('preview');
  preview.innerHTML = '';
  const file = this.files[0];
  if (!file) return;
  const url = URL.createObjectURL(file);
  if (file.type.startsWith('image/')) preview.innerHTML = `<img src="${url}" class="img-fluid rounded">`;
  if (file.type.startsWith('video/')) preview.innerHTML = `<video src="${url}" class="img-fluid rounded" controls></video>`;
});

document.querySelectorAll('.like-form').forEach(f => {
  f.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(location.href, {
      method: 'POST',
      body: new FormData(f),
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).then(r => r.json()).then(data => {
      if ('likes' in data) {
        const btn = f.querySelector('button');
        btn.textContent = (data.liked ? 'Unlike' : 'Like') + ' (' + data.likes + ')';
      }
    });
  });
});

document.querySelectorAll('.comment-form').forEach(f => {
  f.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(location.href, {
      method: 'POST',
      body: new FormData(f),
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).then(r => r.json()).then(data => {
      if (data.comment) {
        const div = document.createElement('div');
        div.className = 'mt-2 p-2 bg-light rounded';
        div.textContent = data.comment;
        f.parentNode.insertBefore(div, f);
        f.reset();
      }
    });
  });
});
</script>
</body>
</html>
