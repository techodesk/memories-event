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

$existingImages = [];
if (!empty($news['image_urls'])) {
    $existingImages = json_decode($news['image_urls'], true) ?: [];
} elseif (!empty($news['image_url'])) {
    $existingImages[] = $news['image_url'];
}

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM news_reads WHERE news_id=?')->execute([$newsId]);
    $pdo->prepare('DELETE FROM news WHERE id=?')->execute([$newsId]);
    header('Location: news_admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $order = json_decode($_POST['image_order'] ?? '[]', true) ?: [];
    $uploaded = [];
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if (is_uploaded_file($tmp)) {
                $uploaded[] = $uploader->uploadToFolder('news', $tmp, $_FILES['images']['name'][$i]);
            }
        }
    }
    $allImages = array_merge($order, $uploaded);
    if (!$allImages) {
        $allImages = $existingImages;
    }
    $imageUrl = $allImages[0] ?? null;
    $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, image_url=?, image_urls=? WHERE id=?');
    $stmt->execute([
        $_POST['title'],
        $_POST['content'],
        $imageUrl,
        $allImages ? json_encode($allImages) : null,
        $newsId
    ]);
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
            <?php if ($existingImages): ?>
            <div class="mb-3">
                <label class="form-label">Current Order</label>
                <ul id="image-list" style="list-style:none;padding:0;">
                    <?php foreach ($existingImages as $img): ?>
                    <li class="mb-2 d-flex align-items-center">
                        <input type="hidden" name="current_images[]" value="<?= htmlspecialchars($img) ?>">
                        <img src="<?= htmlspecialchars($img) ?>" alt="" style="max-width:120px;height:auto;margin-right:10px;">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-secondary move-up">&uarr;</button>
                            <button type="button" class="btn btn-secondary move-down">&darr;</button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <input type="hidden" name="image_order" id="image_order">
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Content</label>
                <textarea name="content" rows="3" class="form-control"><?= htmlspecialchars($news['content']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-accent">Save</button>
            <a href="news_edit.php?id=<?= $newsId ?>&delete=1" class="btn btn-danger ms-2" onclick="return confirm('Delete this news post?')"><i class="bi bi-trash"></i></a>
        </form>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('image-list');
    if (!list) return;
    var orderInput = document.getElementById('image_order');
    function updateOrder() {
        var arr = [];
        list.querySelectorAll('input[name="current_images[]"]').forEach(function (el) {
            arr.push(el.value);
        });
        orderInput.value = JSON.stringify(arr);
    }
    list.addEventListener('click', function (e) {
        if (e.target.classList.contains('move-up')) {
            var li = e.target.closest('li');
            if (li.previousElementSibling) {
                li.parentNode.insertBefore(li, li.previousElementSibling);
                updateOrder();
            }
        }
        if (e.target.classList.contains('move-down')) {
            var li = e.target.closest('li');
            if (li.nextElementSibling) {
                li.parentNode.insertBefore(li.nextElementSibling, li);
                updateOrder();
            }
        }
    });
    updateOrder();
});
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
