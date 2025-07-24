<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Example</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url("https://kaljusaar.ams3.cdn.digitaloceanspaces.com/uploads/taust.jpg");
            background-color: #E9E3DF;
        }
        /* Optional: Form shadow and max width for aesthetics */
        .center-form {
            min-width: 320px;
            max-width: 600px;
            width: 100%;
        }
    </style>
</head>
<body>
<?php
$config = require __DIR__ . '/../config/config.php';
$memDbConf = $config['db_memories'];
$pdo = new PDO(
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
$slug = $_GET['slug'] ?? '';
$code = $_GET['code'] ?? ($_GET['invite'] ?? '');
$stmt = $pdo->prepare('SELECT * FROM forms WHERE slug=?');
$stmt->execute([$slug]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    http_response_code(404);
    echo 'Form not found';
    exit;
}
$guestId = null;
if ($code !== '') {
    $gStmt = $emPdo->prepare('SELECT id FROM guests WHERE invite_code=? AND rsvp_status="Accepted"');
    $gStmt->execute([$code]);
    $guestId = $gStmt->fetchColumn() ?: null;
}
$already = false;
if ($guestId) {
    $chk = $pdo->prepare('SELECT id FROM form_submissions WHERE form_id=? AND guest_id=?');
    $chk->execute([$form['id'], $guestId]);
    $already = (bool)$chk->fetchColumn();
}
$fields = json_decode($form['fields'], true) ?: [];
?>
<div class="container-fluid py-4" style="background-image: url("https://kaljusaar.ams3.cdn.digitaloceanspaces.com/uploads/taust.jpg"; background-color: #f5f3ee;">
  <div class="d-flex justify-content-center">
<?php if ($already): ?>
    <div class="alert alert-success">Aitäh vastuse eest!</div>
<?php else: ?>
    <form id="ajax-form" class="bg-white p-3 rounded shadow-sm center-form">
        <?php foreach ($fields as $field): ?>
            <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars($field['label']) ?></label>
                <?php if ($field['type'] === 'textarea'): ?>
                    <textarea name="<?= htmlspecialchars($field['name']) ?>" class="form-control"></textarea>
                <?php elseif ($field['type'] === 'select'): ?>
                    <select name="<?= htmlspecialchars($field['name']) ?>" class="form-select">
                        <?php foreach (explode(',', $field['options']) as $opt): $opt = trim($opt); ?>
                            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach ?>
                    </select>
                <?php elseif ($field['type'] === 'radio'): $i = 0; ?>
                    <div class="text-center">
                        <div class="d-inline-flex gap-4 flex-wrap">
                        <?php foreach (explode(',', $field['options']) as $opt): $opt = trim($opt); $i++; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input auto-submit" type="radio" name="<?= htmlspecialchars($field['name']) ?>" value="<?= htmlspecialchars($opt) ?>" id="<?= htmlspecialchars($field['name'] . '_' . $i) ?>">
                                <label class="form-check-label" for="<?= htmlspecialchars($field['name'] . '_' . $i) ?>">
                                    <?= htmlspecialchars($opt) ?>
                                </label>
                            </div>
                        <?php endforeach ?>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="<?= htmlspecialchars($field['type']) ?>" name="<?= htmlspecialchars($field['name']) ?>" class="form-control">
                <?php endif ?>
            </div>
        <?php endforeach ?>
        <!-- No need for a submit button if auto-submit, but uncomment if needed:
        <button type="submit" class="btn btn-primary">Submit</button>
        -->
    </form>
<?php endif ?>
  </div>
</div>
<script>
<?php if (!$already): ?>
    var form = document.getElementById('ajax-form');
    function sendForm() {
        fetch('/form_submit.php?slug=<?= urlencode($slug) ?>&code=<?= urlencode($code) ?>', {
            method: 'POST',
            body: new FormData(form)
        }).then(function () {
            form.innerHTML = '<div class="alert alert-success">Aitäh vastuse eest!</div>';
        });
    }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        sendForm();
    });
    form.querySelectorAll('.auto-submit').forEach(function (el) {
        el.addEventListener('change', sendForm);
    });
<?php endif ?>
</script>
</body>
</html>
