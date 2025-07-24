<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

$memDbConf = $config['db_memories'];
$memPdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Fetch events for dropdown
$events = $memPdo->query("SELECT id, event_name FROM events ORDER BY event_date DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
$eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : ($events[0]['id'] ?? 0);

// Handle delete
if (isset($_GET['delete'])) {
    $pid = intval($_GET['delete']);
    $memPdo->prepare('DELETE FROM post_comments WHERE post_id=?')->execute([$pid]);
    $memPdo->prepare('DELETE FROM post_likes WHERE post_id=?')->execute([$pid]);
    $memPdo->prepare('DELETE FROM event_posts WHERE id=?')->execute([$pid]);
    header('Location: uploads.php?event_id=' . $eventId);
    exit;
}

$posts = [];
if ($eventId) {
    $stmt = $memPdo->prepare('SELECT * FROM event_posts WHERE event_id=? ORDER BY created_at DESC');
    $stmt->execute([$eventId]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = 'Uploads';
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Uploads</h2>
        <form method="get" class="mb-3">
            <select name="event_id" class="form-select" style="max-width:240px;display:inline-block" onchange="this.form.submit()">
                <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"<?= $e['id'] == $eventId ? ' selected' : '' ?>>
                        <?= htmlspecialchars($e['event_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="table-responsive" style="border-radius:var(--border-radius);overflow:hidden;">
            <table class="table table-dark table-hover align-middle mb-0" style="background: var(--card-bg); min-width:600px;">
                <thead style="background: var(--sidebar-bg)">
                    <tr>
                        <th>File</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td><a href="<?= htmlspecialchars($p['file_url']) ?>" target="_blank">Download</a></td>
                        <td><?= htmlspecialchars($p['session_id']) ?></td>
                        <td><?= htmlspecialchars($p['created_at']) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($p['file_url']) ?>" class="btn btn-secondary btn-sm" download>Download</a>
                            <a href="?event_id=<?= $eventId ?>&delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this upload?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
