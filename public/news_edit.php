<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/memories/UploadManager.php';

$newsId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$newsId) {
    die('News ID missing');
}

$memDbConf = $config['db_memories'];
$pdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$uploader = new UploadManager($config['do_spaces']);

$stmt = $pdo->prepare('SELECT * FROM news WHERE id=?');
$stmt->execute([$newsId]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$news) {
    die('News not found');
}

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM news_reads WHERE news_id=?')->execute([$newsId]);
    $pdo->prepare('DELETE FROM news WHERE id=?')->execute([$newsId]);
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
    $imageUrl = $news['image_url'];
    $imageUrls = $news['image_urls'];
    if ($images) {
        $imageUrl = $images[0];
        $imageUrls = json_encode($images);
    }
    $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, image_url=?, image_urls=? WHERE id=?');
    $stmt->execute([$_POST['title'], $_POST['content'], $imageUrl, $imageUrls, $newsId]);
    header('Location: news_admin.php');
    exit;
}

$page_title = 'Edit News';
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Edit News</h2>
        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($news['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Images</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
            </div>
            <div class="mb-3">
                <label class="form-label">Content</label>
                <textarea name="content" rows="3" class="form-control"><?= htmlspecialchars($news['content']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-accent">Save</button>
            <a href="news_edit.php?id=<?= $newsId ?>&delete=1" class="btn btn-danger ms-2" onclick="return confirm('Delete this news post?')"><i class="bi bi-trash"></i></a>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
