<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/guests/guest_helpers.php';

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if (!$event_id) die("Event ID missing!");

// --- DB Connections ---
$memDbConf = $config['db_memories'];
$memPdo = new PDO("mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}", $memDbConf['user'], $memDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$emDbConf = $config['db_event_manager'];
$emPdo = new PDO("mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}", $emDbConf['user'], $emDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// --- UPDATE EVENT DETAILS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $stmt = $memPdo->prepare(
        "UPDATE events SET event_name=?, event_date=?, event_location=?, description=?, status=? WHERE id=?"
    );
    $stmt->execute([
        $_POST['event_name'],
        $_POST['event_date'],
        $_POST['event_location'],
        $_POST['description'],
        $_POST['status'],
        $event_id
    ]);
    header("Location: event.php?event_id=$event_id&updated=1");
    exit;
}

// --- FETCH EVENT ---
$evt = $memPdo->prepare("SELECT * FROM events WHERE id=?");
$evt->execute([$event_id]);
$event = $evt->fetch(PDO::FETCH_ASSOC);

// --- ADD GUEST(s) --- always allowed
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_guest_ids'])
) {
    foreach ($_POST['add_guest_ids'] as $gid) {
        $stmt2 = $emPdo->prepare("SELECT invite_code FROM guests WHERE id=?");
        $stmt2->execute([$gid]);
        $invite_code = $stmt2->fetchColumn();
        $memPdo
            ->prepare(
                "INSERT IGNORE INTO event_guests (event_id, guest_id, invitation_code) VALUES (?, ?, ?)"
            )
            ->execute([$event_id, $gid, $invite_code]);

    }
    header("Location: event.php?event_id=$event_id");
    exit;
}

// --- REMOVE GUEST ---
if (isset($_GET['remove_guest'])) {
    $stmt = $memPdo->prepare("DELETE FROM event_guests WHERE event_id=? AND guest_id=?");
    $stmt->execute([$event_id, intval($_GET['remove_guest'])]);
    header("Location: event.php?event_id=$event_id");
    exit;
}

// --- CURRENT GUESTS ---
$idsStmt = $memPdo->prepare("SELECT guest_id FROM event_guests WHERE event_id=?");
$idsStmt->execute([$event_id]);
$guestIds = $idsStmt->fetchAll(PDO::FETCH_COLUMN);
$added_guests = [];
if ($guestIds) {
    $placeholders = implode(',', array_fill(0, count($guestIds), '?'));
    $gStmt = $emPdo->prepare("SELECT id, name, email, invite_code FROM guests WHERE id IN ($placeholders)");
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

// --- AVAILABLE GUESTS (not added yet) ---
$already = array_column($added_guests, 'guest_id');
$already_ids = $already ? implode(',', $already) : '0';
$all = $emPdo->query("SELECT id, name, email, invite_code FROM guests WHERE id NOT IN ($already_ids) AND rsvp_status='Accepted' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// --- Theming Stuff ---
$page_title = "Edit Event";
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Edit Event</h2>
        <form method="POST" class="mb-4">
            <input type="hidden" name="update_event" value="1">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="event_name" class="form-control" value="<?= htmlspecialchars($event['event_name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="event_date" class="form-control" value="<?= htmlspecialchars($event['event_date']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Location</label>
                    <input type="text" name="event_location" class="form-control" value="<?= htmlspecialchars($event['event_location']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-control"><?= htmlspecialchars($event['description']) ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Created', 'Started', 'Ended'] as $st): ?>
                            <option value="<?= $st ?>"<?= $event['status'] === $st ? ' selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-accent mt-2 px-4">Save Changes</button>
                </div>
            </div>
        </form>
        <hr>
        <h5 class="mb-2">Guests for This Event</h5>
        <div class="table-responsive mb-3">
            <table class="table table-dark table-hover align-middle mb-0" style="background: var(--card-bg);">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Invite Code</th>
                        <th style="width:70px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($added_guests as $g): ?>
                    <tr>
                        <td><?= htmlspecialchars($g['name']) ?></td>
                        <td><?= htmlspecialchars($g['email']) ?></td>
                        <td><?= htmlspecialchars($g['invite_code']) ?></td>
                        <td>
                            <a href="?event_id=<?= $event_id ?>&remove_guest=<?= $g['guest_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove guest?')">
                                <i class="bi bi-x"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>


        <h5 class="mb-2 mt-4">Add Guests</h5>
        <form method="post" class="mb-0">
            <div class="row g-2">
                <div class="col-md-8">
                    <?php echo renderGuestSelectInput($all, [], 'add_guest_ids[]'); ?>
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
