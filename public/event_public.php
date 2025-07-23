<?php
$config = require __DIR__ . '/../config/config.php';

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
<main class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="p-4" style="background: var(--card-bg); border-radius: var(--border-radius); max-width:720px; width:100%;">
        <?php if (!empty($event['header_image'])): ?>
            <img src="<?= htmlspecialchars($event['header_image']) ?>" class="img-fluid mb-3" alt="header" style="border-radius:var(--border-radius);">
        <?php endif; ?>
        <h1 class="mb-3 text-center"><?= htmlspecialchars($event['event_name']) ?></h1>
        <p><strong>Date:</strong> <?= htmlspecialchars($event['event_date']) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($event['event_location']) ?></p>
        <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
