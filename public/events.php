<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';

// --- DB Connections ---
$memDbConf = $config['db_memories'];
$memPdo = new PDO("mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}", $memDbConf['user'], $memDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$emDbConf = $config['db_event_manager'];
$emPdo = new PDO("mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}", $emDbConf['user'], $emDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// --- ADD EVENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_name'])) {
    $stmt = $memPdo->prepare("INSERT INTO events (event_name, event_date, event_location, description, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['event_name'],
        $_POST['event_date'],
        $_POST['event_location'],
        $_POST['description'],
        $_SESSION['user_id']
    ]);
    $event_id = $memPdo->lastInsertId();

    // --- Add selected guests (if any) ---
    if (!empty($_POST['guest_ids'])) {
        foreach ($_POST['guest_ids'] as $gid) {
            $stmt2 = $emPdo->prepare("SELECT invite_code FROM guests WHERE id=?");
            $stmt2->execute([$gid]);
            $invite_code = $stmt2->fetchColumn();
            $memPdo->prepare("INSERT INTO event_guests (event_id, guest_id, invitation_code) VALUES (?, ?, ?)")
                   ->execute([$event_id, $gid, $invite_code]);
        }
    }

    header("Location: events");
    exit;
}

// --- DELETE EVENT ---
if (isset($_GET['delete'])) {
    $stmt = $memPdo->prepare("DELETE FROM events WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: events.php");
    exit;
}

// --- FETCH EVENTS ---
$stmt = $memPdo->query("SELECT * FROM events ORDER BY event_date DESC, id DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH GUESTS (for add form) ---
$all_guests = $emPdo->query("SELECT id, name, email FROM guests ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// --- Theming Stuff ---
$page_title = "Events";
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Events</h2>
        <form method="POST" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="event_name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="event_date" class="form-control">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Location</label>
                    <input type="text" name="event_location" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-control"></textarea>
                </div>
<select class="form-select" name="guest_ids[]" id="guestSelect" multiple>
    <?php foreach ($all_guests as $g): ?>
        <option value="<?= $g['id'] ?>">
            <?= htmlspecialchars((string)($g['name'] ?? '')) ?>
            (<?= htmlspecialchars((string)($g['email'] ?? '-')) ?>)
        </option>
    <?php endforeach; ?>
</select>


                <div class="col-12">
                    <button type="submit" class="btn btn-accent mt-2 px-4">Add Event</button>
                </div>
            </div>
        </form>
        <div class="table-responsive" style="border-radius:var(--border-radius);overflow:hidden;">
            <table class="table table-dark table-hover align-middle mb-0" style="background: var(--card-bg); min-width:600px;">
                <thead style="background: var(--sidebar-bg)">
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Description</th>
                        <th style="width:120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['event_name']) ?></td>
                        <td><?= htmlspecialchars($event['event_date']) ?></td>
                        <td><?= htmlspecialchars($event['event_location']) ?></td>
                        <td><?= htmlspecialchars($event['description']) ?></td>
                        <td>
                            <a href="event.php?event_id=<?= $event['id'] ?>" class="btn btn-accent btn-sm">Edit</a>
                            <a href="events.php?delete=<?= $event['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this event?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
