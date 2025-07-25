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
$emDbConf = $config['db_event_manager'];

$formId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$formId) {
    $forms = $pdo->query('SELECT id, name FROM forms ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    $page_title = 'Form Results';
    $is_staff = true;
    $display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/../templates/sidebar.php';
    include __DIR__ . '/../templates/topbar.php';
    echo "<main class=\"dashboard-main\"><div class=\"dashboard-section mb-4\">";
    echo "<h2 class=\"mb-3 section-title\">Form Results</h2>";
    echo '<ul class="list-unstyled">';
    foreach ($forms as $f) {
        echo '<li><a href="forms_results.php?id=' . $f['id'] . '">' . htmlspecialchars($f['name']) . '</a></li>';
    }
    echo '</ul></div></main>';
    include __DIR__ . '/../templates/footer.php';
    return;
}

$stmt = $pdo->prepare('SELECT * FROM forms WHERE id=?');
$stmt->execute([$formId]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    die('Form not found');
}

$fields = json_decode($form['fields'], true) ?: [];
$fieldLabels = [];
$stats = [];
foreach ($fields as $f) {
    $fieldLabels[$f['name']] = $f['label'];
    if (in_array($f['type'], ['select','radio'])) {
        $opts = array_map('trim', explode(',', $f['options']));
        foreach ($opts as $o) {
            $stats[$f['name']][$o] = 0;
        }
    }
}

$subsStmt = $pdo->prepare(
    'SELECT * FROM form_submissions WHERE form_id=? ORDER BY submitted_at DESC'
);
$subsStmt->execute([$formId]);
$submissions = $subsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch guest names/emails using the event manager connection
$emPdo = new PDO(
    "mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}",
    $emDbConf['user'],
    $emDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$guestMap = [];
$guestIds = array_unique(array_filter(array_column($submissions, 'guest_id')));
if ($guestIds) {
    $placeholders = implode(',', array_fill(0, count($guestIds), '?'));
    $gStmt = $emPdo->prepare("SELECT id, name, email FROM guests WHERE id IN ($placeholders)");
    $gStmt->execute($guestIds);
    foreach ($gStmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
        $guestMap[$g['id']] = $g;
    }
}
foreach ($submissions as &$sub) {
    if ($sub['guest_id'] && isset($guestMap[$sub['guest_id']])) {
        $sub['guest_name']  = $guestMap[$sub['guest_id']]['name'];
        $sub['guest_email'] = $guestMap[$sub['guest_id']]['email'];
    }
}
unset($sub);
foreach ($submissions as $s) {
    $data = json_decode($s['data'], true) ?: [];
    foreach ($stats as $fname => $opts) {
        if (isset($data[$fname]) && isset($opts[$data[$fname]])) {
            $stats[$fname][$data[$fname]]++;
        }
    }
}

$page_title = 'Form Results';
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title"><?= htmlspecialchars($form['name']) ?> Submissions</h2>
        <div class="table-responsive mb-4">
            <table class="table table-dark table-hover align-middle mb-0" style="background: var(--card-bg);">
                <thead>
                    <tr>
                        <th>Submitted</th>
                        <th>Guest</th>
<?php foreach ($fields as $f): ?>
                        <th><?= htmlspecialchars($f['label']) ?></th>
<?php endforeach ?>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($submissions as $sub): $data = json_decode($sub['data'], true) ?: []; ?>
                    <tr>
                        <td><?= htmlspecialchars($sub['submitted_at']) ?></td>
                        <td><?= htmlspecialchars($sub['guest_name'] ?? 'Anonymous') ?></td>
<?php foreach ($fields as $f): ?>
                        <td><?= htmlspecialchars($data[$f['name']] ?? '') ?></td>
<?php endforeach ?>
                    </tr>
<?php endforeach ?>
                </tbody>
            </table>
        </div>
<?php if ($stats): ?>
        <h3 class="mb-3">Statistics</h3>
<?php foreach ($stats as $fname => $opts): ?>
        <h5><?= htmlspecialchars($fieldLabels[$fname]) ?></h5>
        <ul>
<?php foreach ($opts as $opt => $cnt): ?>
            <li><?= htmlspecialchars($opt) ?>: <?= $cnt ?></li>
<?php endforeach ?>
        </ul>
<?php endforeach ?>
<?php endif ?>
    </div>
</main>
<?php include __DIR__ . '/../templates/footer.php'; ?>
