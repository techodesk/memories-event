<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';
$memDbConf = $config['db_memories'];
$pdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function classify_device(string $ua): string {
    $ua = strtolower($ua);
    if (strpos($ua, 'iphone') !== false) return 'iPhone';
    if (strpos($ua, 'ipad') !== false) return 'iPad';
    if (strpos($ua, 'android') !== false) return 'Android';
    if (strpos($ua, 'windows') !== false) return 'Windows';
    if (strpos($ua, 'macintosh') !== false || strpos($ua, 'mac os') !== false) return 'Mac';
    return 'Other';
}

$newsId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$newsId) {
    $news = $pdo->query('SELECT id,title FROM news ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    $page_title = 'News Stats';
    $is_staff = true;
    $display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/../templates/sidebar.php';
    include __DIR__ . '/../templates/topbar.php';
    echo "<main class=\"dashboard-main\"><div class=\"dashboard-section mb-4\">";
    echo "<h2 class=\"mb-3 section-title\">News Stats</h2>";
    echo '<ul class="list-unstyled">';
    foreach ($news as $n) {
        echo '<li><a href="news_stats.php?id=' . $n['id'] . '">' . htmlspecialchars($n['title']) . '</a></li>';
    }
    echo '</ul></div></main>';
    include __DIR__ . '/../templates/footer.php';
    return;
}

$stmt = $pdo->prepare('SELECT title FROM news WHERE id=?');
$stmt->execute([$newsId]);
$title = $stmt->fetchColumn();
if (!$title) {
    die('News not found');
}
$readsStmt = $pdo->prepare('SELECT guest_id, device_info FROM news_reads WHERE news_id=?');
$readsStmt->execute([$newsId]);
$reads = $readsStmt->fetchAll(PDO::FETCH_ASSOC);
$guestIds = array_column($reads, 'guest_id');
$guestNames = [];
if ($guestIds) {
    $emDbConf = $config['db_event_manager'];
    $emPdo = new PDO(
        "mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}",
        $emDbConf['user'],
        $emDbConf['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $in = implode(',', array_fill(0, count($guestIds), '?'));
    $gStmt = $emPdo->prepare("SELECT id,name FROM guests WHERE id IN ($in)");
    $gStmt->execute($guestIds);
    $tmp = $gStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($guestIds as $gid) {
        if (isset($tmp[$gid])) {
            $guestNames[$tmp[$gid]] = true;
        }
    }
    $guestNames = array_keys($guestNames);
}
$deviceData = array_column($reads, 'device_info');
$devices = [];
foreach ($deviceData as $ua) {
    $dev = classify_device($ua ?: '');
    $devices[$dev] = ($devices[$dev] ?? 0) + 1;
}
$page_title = 'News Stats';
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Stats for <?= htmlspecialchars($title) ?></h2>
        <p>Total reads: <?= count($reads) ?></p>
        <h5>Devices</h5>
        <ul>
<?php foreach ($devices as $d => $c): ?>
            <li><?= htmlspecialchars($d) ?>: <?= $c ?></li>
<?php endforeach ?>
        </ul>
<?php if ($guestNames): ?>
        <h5 class="mt-4">Readers</h5>
        <ul>
<?php foreach ($guestNames as $name): ?>
            <li><?= htmlspecialchars($name) ?></li>
<?php endforeach ?>
        </ul>
<?php endif ?>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
