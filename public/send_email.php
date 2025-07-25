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
require_once __DIR__ . '/../src/Translation.php';

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

$tr = new Translation();
$guestManager = new GuestManager($emPdo, $memPdo);
$all_guests = $guestManager->fetchAllGuests();

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$selected_guests = [];
if ($event_id) {
    $idsStmt = $memPdo->prepare('SELECT guest_id FROM event_guests WHERE event_id=?');
    $idsStmt->execute([$event_id]);
    $selected_guests = $idsStmt->fetchAll(PDO::FETCH_COLUMN);
}

$sent = false;
$error = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['guest_ids']) &&
    isset($_POST['subject'], $_POST['message'])
) {
    $guestIds = array_map('intval', (array)$_POST['guest_ids']);
    $placeholders = implode(',', array_fill(0, count($guestIds), '?'));
    $stmt = $emPdo->prepare("SELECT name, email FROM guests WHERE id IN ($placeholders)");
    $stmt->execute($guestIds);
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $smtp = $config['smtp'];
    foreach ($guests as $g) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->Port = $smtp['port'];
            $mail->SMTPSecure = $smtp['encryption'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];
            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($g['email'], $g['name']);
            $mail->Subject = $_POST['subject'];
            $mail->Body = $_POST['message'];
            $mail->send();
        } catch (Exception $e) {
            $error = $e->getMessage();
            break;
        }
    }
    if (!$error) {
        $sent = true;
    }
}

$page_title = 'Send Email';
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Send Email</h2>
        <?php if ($sent && !$error): ?>
            <div class="alert alert-success">Email sent.</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Recipients</label>
                <?php echo renderGuestSelectInput($all_guests, $selected_guests); ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-accent px-4">Send</button>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
