<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Example</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
	body { 
	background: #EFECE7;
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
<div class="container py-4">
    <form id="ajax-form">
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
                    <?php foreach (explode(',', $field['options']) as $opt): $opt = trim($opt); $i++; ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input auto-submit" type="radio" name="<?= htmlspecialchars($field['name']) ?>" value="<?= htmlspecialchars($opt) ?>" id="<?= htmlspecialchars($field['name'] . '_' . $i) ?>">
                            <label class="form-check-label" for="<?= htmlspecialchars($field['name'] . '_' . $i) ?>">
                                <?= htmlspecialchars($opt) ?>
                            </label>
                        </div>
                    <?php endforeach ?>
                <?php else: ?>
                    <input type="<?= htmlspecialchars($field['type']) ?>" name="<?= htmlspecialchars($field['name']) ?>" class="form-control">
                <?php endif ?>
            </div>
        <?php endforeach ?>
       <!-- <button type="submit" class="btn btn-primary">Submit</button>-->
    </form>
</div>
<script>
    var form = document.getElementById('ajax-form');
    function sendForm() {
        fetch('/form_submit.php?slug=<?= urlencode($slug) ?>', {
            method: 'POST',
            body: new FormData(form)
        }).then(function () { form.innerHTML = '<div class="alert alert-success">Aitäh vastuse eest!</div>'; });
    }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        sendForm();
    });
    form.querySelectorAll('.auto-submit').forEach(function (el) {
        el.addEventListener('change', sendForm);
    });
</script>
</body>
</html>
