<?php
$config = require __DIR__ . '/../config/config.php';

$memDbConf = $config['db_memories'];
$memPdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$error = '';
$event = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['invite_code'] ?? '');
    if ($code === '') {
        $error = 'Enter invite code';
    } else {
        $stmt = $memPdo->prepare('SELECT e.* FROM event_guests eg JOIN events e ON eg.event_id = e.id WHERE eg.invitation_code = ? LIMIT 1');
        $stmt->execute([$code]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            $error = 'Invite code not found';
        }
    }
}

$page_title = 'Find Event';
include __DIR__ . '/../templates/header.php';
?>
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="p-4" style="background: var(--card-bg); border-radius: var(--border-radius); box-shadow: 0 4px 24px #0001; max-width:420px; width:100%;">
        <h2 class="mb-3 text-center">Find Your Event</h2>
        <form method="post" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Invite Code</label>
                <input type="text" name="invite_code" class="form-control" required>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 text-center mb-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-accent w-100">Search</button>
        </form>
        <?php if ($event): ?>
            <div class="card p-3" style="background: var(--sidebar-bg);">
                <h5><?= htmlspecialchars($event['event_name']) ?></h5>
                <p class="mb-1"><strong>Date:</strong> <?= htmlspecialchars($event['event_date']) ?></p>
                <p class="mb-2"><strong>Location:</strong> <?= htmlspecialchars($event['event_location']) ?></p>
                <a href="/e/<?= urlencode($event['public_id']) ?>" class="btn btn-accent">Open Event Page</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
