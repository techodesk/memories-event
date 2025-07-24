<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/memories/UploadManager.php';

$memDbConf = $config['db_memories'];
$pdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$uploader = new UploadManager($config['do_spaces']);

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare('DELETE FROM news_reads WHERE news_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM news WHERE id=?')->execute([$id]);
    header('Location: news_admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $images = [];
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if (is_uploaded_file($tmp)) {
                $images[] = $uploader->uploadToFolder('news', $tmp, $_FILES['images']['name'][$i]);
            }
        }
    }
    $imageUrl = $images[0] ?? null;
    $slug = bin2hex(random_bytes(8));
    $stmt = $pdo->prepare('INSERT INTO news (slug, title, content, image_url, image_urls) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$slug, $_POST['title'], $_POST['content'], $imageUrl, json_encode($images)]);
    header('Location: news_admin');
    exit;
}

$news = $pdo->query('SELECT * FROM news ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'News';
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">News</h2>
        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Images</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
            </div>
            <div class="mb-3">
                <label class="form-label">Content</label>
                <textarea name="content" rows="3" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-accent">Create</button>
        </form>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="background: var(--card-bg);">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Created</th>
                        <th>Link</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($news as $n): ?>
                    <tr>
                        <td><?= htmlspecialchars($n['title']) ?></td>
                        <td><?= htmlspecialchars($n['created_at']) ?></td>
                        <td><a href="/news/<?= urlencode($n['slug']) ?>" target="_blank">Open</a></td>
                        <td>
                            <a href="news_edit.php?id=<?= $n['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <a href="news_admin.php?delete=<?= $n['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this post?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
