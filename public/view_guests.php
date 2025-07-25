<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Translation.php';

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if (!$event_id) {
    die('Event ID missing!');
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

$evt = $memPdo->prepare('SELECT * FROM events WHERE id=?');
$evt->execute([$event_id]);
$event = $evt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    die('Event not found');
}

$idsStmt = $memPdo->prepare('SELECT guest_id FROM event_guests WHERE event_id=?');
$idsStmt->execute([$event_id]);
$guestIds = $idsStmt->fetchAll(PDO::FETCH_COLUMN);
$added_guests = [];
if ($guestIds) {
    $placeholders = implode(',', array_fill(0, count($guestIds), '?'));
    $gStmt = $emPdo->prepare("SELECT id, name, email, invitation_code FROM guests WHERE id IN ($placeholders)");
    $gStmt->execute($guestIds);
    $info = [];
    foreach ($gStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $info[$row['id']] = $row;
    }
    foreach ($guestIds as $gid) {
        if (isset($info[$gid])) {
            $row = $info[$gid];
            $row['guest_id'] = $gid;
            $added_guests[] = $row;
        }
    }
}

$tr = new Translation();

$page_title = $tr->t('guests_for_this_event');
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">
            <?= htmlspecialchars($tr->t('guests_for_this_event')) ?>: 
            <span class="text-accent"><?= htmlspecialchars($event['event_name']) ?></span>
        </h2>
        <div class="table-responsive mb-3">
            <table class="table table-dark table-hover align-middle mb-0" style="background: var(--card-bg);">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars($tr->t('name')) ?></th>
                        <th>Email</th>
                        <th>Invitation Code</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($added_guests as $g): ?>
                    <tr>
                        <td><?= htmlspecialchars($g['name']) ?></td>
                        <td><?= htmlspecialchars($g['email']) ?></td>
                        <td><?= htmlspecialchars($g['invitation_code']) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
