<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';

// Get event_id
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if (!$event_id) {
    die('Event ID missing!');
}

// DB connections
$memDbConf = $config['db_memories'];
$memPdo = new PDO("mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}", $memDbConf['user'], $memDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$emDbConf = $config['db_event_manager'];
$emPdo = new PDO("mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}", $emDbConf['user'], $emDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// ADD GUEST(s)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_guest_ids'])) {
    foreach ($_POST['add_guest_ids'] as $gid) {
        // Get invitation_code from event-manager
        $g = $emPdo->prepare("SELECT invitation_code FROM guests WHERE id=?");
        $g->execute([$gid]);
        $code = $g->fetchColumn();
        $stmt = $memPdo->prepare("INSERT INTO event_guests (event_id, guest_id, invitation_code) VALUES (?, ?, ?)");
        $stmt->execute([$event_id, $gid, $code]);
    }
    header("Location: guests.php?event_id=" . $event_id);
    exit;
}
// REMOVE GUEST
if (isset($_GET['remove_guest'])) {
    $stmt = $memPdo->prepare("DELETE FROM event_guests WHERE event_id=? AND guest_id=?");
    $stmt->execute([$event_id, intval($_GET['remove_guest'])]);
    header("Location: guests.php?event_id=" . $event_id);
    exit;
}

// Get event info (optional)
$evt = $memPdo->prepare("SELECT * FROM events WHERE id=?");
$evt->execute([$event_id]);
$event = $evt->fetch(PDO::FETCH_ASSOC);

// Guests already in this event:
$q = $memPdo->prepare("SELECT eg.*, g.name, g.email, g.invitation_code FROM event_guests eg JOIN {$emDbConf['dbname']}.guests g ON eg.guest_id=g.id WHERE eg.event_id=?");
$q->execute([$event_id]);
$added_guests = $q->fetchAll(PDO::FETCH_ASSOC);

// Guests NOT yet added
$already = array_column($added_guests, 'guest_id');
$already_ids = $already ? implode(',', $already) : '0';
$all = $emPdo->query("SELECT id, name, email, invitation_code FROM guests WHERE id NOT IN ($already_ids)")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Manage Guests";
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Guests for Event: <span class="text-accent"><?= htmlspecialchars($event['event_name']) ?></span></h2>
        
        <h5 class="mb-2">Current Guests</h5>
        <div class="table-responsive mb-3">
            <table class="table table-dark table-hover align-middle mb-0" style="background: var(--card-bg);">
                <thead>
                    <tr>
                        <th>Name</th><th>Email</th><th>Invitation Code</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($added_guests as $g): ?>
                    <tr>
                        <td><?= htmlspecialchars($g['name']) ?></td>
                        <td><?= htmlspecialchars($g['email']) ?></td>
                        <td><?= htmlspecialchars($g['invitation_code']) ?></td>
                        <td>
                            <a href="?event_id=<?= $event_id ?>&remove_guest=<?= $g['guest_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove guest?')">
                                <i class="bi bi-x"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        
        <h5 class="mb-2 mt-4">Add Guests</h5>
        <form method="post" class="mb-0">
            <div class="row g-2">
                <div class="col-md-8">
                    <select name="add_guest_ids[]" class="form-select" multiple size="6" required>
                        <?php foreach ($all as $g): ?>
                            <option value="<?= $g['id'] ?>">
                                <?= htmlspecialchars($g['name']) ?> (<?= htmlspecialchars($g['email']) ?>)
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4 align-self-end">
                    <button class="btn btn-accent px-4" type="submit">Add Selected</button>
                </div>
            </div>
            <small class="text-secondary mt-2 d-block">Hold Ctrl/Cmd to select multiple guests.</small>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
