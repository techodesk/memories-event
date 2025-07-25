<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Exceptions\MailerSendHttpException;
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
$logFile = __DIR__ . '/../logs/mailersend.log';

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
    $msConf = $config['mailersend'];
    $recipients = [];
    foreach ($guests as $g) {
        $recipients[] = new Recipient($g['email'], $g['name']);
    }
    try {
        $mailersend = new MailerSend(['api_key' => $msConf['api_key']]);
        $emailParams = (new EmailParams())
            ->setFrom($msConf['from_email'])
            ->setFromName($msConf['from_name'])
            ->setRecipients($recipients)
            ->setSubject($_POST['subject'])
            ->setHtml($_POST['message'])
            ->setText(strip_tags($_POST['message']));
        $mailersend->email->send($emailParams);
        $sent = true;
    } catch (MailerSendHttpException $e) {
        // Avoid calling getResponse() since the library may not initialize the
        // property correctly. Log just the exception message.
        $error = $e->getMessage();
        error_log("[" . date('Y-m-d H:i:s') . "] " . $error . "\n", 3, $logFile);
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("[" . date('Y-m-d H:i:s') . "] " . $error . "\n", 3, $logFile);
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
