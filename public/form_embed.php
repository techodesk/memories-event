<?php
$config = require __DIR__ . '/../config/config.php';
$memDbConf = $config['db_memories'];
$pdo = new PDO(
    "mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}",
    $memDbConf['user'],
    $memDbConf['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM forms WHERE slug=?');
$stmt->execute([$slug]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    http_response_code(404);
    echo 'Form not found';
    exit;
}
$fields = json_decode($form['fields'], true) ?: [];
?>
<form id="ajax-form">
<?php foreach ($fields as $field): ?>
    <div class="mb-2">
        <label class="form-label"><?= htmlspecialchars($field['label']) ?></label>
        <input type="<?= htmlspecialchars($field['type']) ?>" name="<?= htmlspecialchars($field['name']) ?>" class="form-control">
    </div>
<?php endforeach ?>
    <button type="submit" class="btn btn-primary">Submit</button>
</form>
<script>
    document.getElementById('ajax-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var form = e.target;
        fetch('/form_submit.php?slug=<?= urlencode($slug) ?>', {
            method: 'POST',
            body: new FormData(form)
        }).then(function () { form.innerHTML = '<div class="alert alert-success">Thanks!</div>'; });
    });
</script>
