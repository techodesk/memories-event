<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/guests/GuestManager.php';
require_once __DIR__ . '/../src/guests/guest_helpers.php';
require_once __DIR__ . '/../src/memories/UploadManager.php';

// --- DB Connections ---
$memDbConf = $config['db_memories'];
$memPdo = new PDO("mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}", $memDbConf['user'], $memDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$emDbConf = $config['db_event_manager'];
$emPdo = new PDO("mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}", $emDbConf['user'], $emDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$guestManager = new GuestManager($emPdo, $memPdo);

// --- ADD EVENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_name'])) {
    $uploader = new UploadManager($config['do_spaces']);
    $headerImage = null;
    if (isset($_FILES['header_image']) && is_uploaded_file($_FILES['header_image']['tmp_name'])) {
        $headerImage = $uploader->upload($_FILES['header_image']['tmp_name'], $_FILES['header_image']['name']);
    }
    $publicId = bin2hex(random_bytes(8));
    $stmt = $memPdo->prepare(
        "INSERT INTO events (public_id, event_name, event_date, event_location, description, created_by, status, header_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $publicId,
        $_POST['event_name'],
        $_POST['event_date'],
        $_POST['event_location'],
        $_POST['description'],
        $_SESSION['user_id'],
        'Created',
        $headerImage
    ]);
    $event_id = $memPdo->lastInsertId();

    // --- Add selected guests (if any) ---
    if (!empty($_POST['guest_ids'])) {
        $guestIds = array_map('intval', (array)$_POST['guest_ids']);
        $guestManager->addGuestsToEvent($event_id, $guestIds);
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
$all_guests = $guestManager->fetchAllGuests();

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
        <form method="POST" enctype="multipart/form-data" class="mb-4">
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
                <div class="col-12 col-md-5">
                    <label class="form-label">Header Image</label>
                    <input type="file" name="header_image" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-control"></textarea>
                </div>
                <?php echo renderGuestSelectInput($all_guests); ?>

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
                        <th>Status</th>
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
                        <td><?= htmlspecialchars($event['status'] ?? '') ?></td>
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
