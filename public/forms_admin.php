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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $slug = bin2hex(random_bytes(8));
    $fields = $_POST['fields'] ?? '[]';
    $stmt = $pdo->prepare('INSERT INTO forms (slug, name, fields) VALUES (?, ?, ?)');
    $stmt->execute([$slug, $_POST['name'], $fields]);
    header('Location: forms_admin.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare('DELETE FROM form_submissions WHERE form_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM forms WHERE id=?')->execute([$id]);
    header('Location: forms_admin.php');
    exit;
}

$forms = $pdo->query('SELECT * FROM forms ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Forms';
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Forms</h2>
        <form method="post" id="form-builder" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div id="fields"></div>
            <button type="button" id="add-field" class="btn btn-secondary btn-sm mb-3">Add Field</button>
            <input type="hidden" name="fields" id="fields-input">
            <button type="submit" class="btn btn-accent">Create</button>
        </form>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="background: var(--card-bg);">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Embed</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($forms as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['name']) ?></td>
                        <td><code>&lt;iframe src="/form_embed.php?slug=<?= htmlspecialchars($f['slug']) ?>" style="border:0;width:100%;"&gt;&lt;/iframe&gt;</code></td>
                        <td>
                            <a href="forms_edit.php?id=<?= $f['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <a href="forms_admin.php?delete=<?= $f['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this form?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var fieldsDiv = document.getElementById('fields');
        var fieldsInput = document.getElementById('fields-input');
        document.getElementById('add-field').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'row g-2 mb-2 align-items-end';
            row.innerHTML = '<div class="col"><input type="text" class="form-control" placeholder="Label" data-role="label"></div>' +
                '<div class="col"><input type="text" class="form-control" placeholder="Name" data-role="name"></div>' +
                '<div class="col"><select class="form-select" data-role="type">' +
                '<option value="text">Text</option>' +
                '<option value="email">Email</option>' +
                '<option value="number">Number</option>' +
                '<option value="textarea">Textarea</option>' +
                '<option value="select">Select</option>' +
                '<option value="radio">Radio</option>' +
                '</select></div>' +
                '<div class="col options-col d-none"><input type="text" class="form-control" placeholder="Options (comma separated)" data-role="options"></div>' +
                '<div class="col-auto"><button type="button" class="btn btn-danger btn-sm remove-field">Remove</button></div>';
            fieldsDiv.appendChild(row);
        });
        fieldsDiv.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-field')) {
                e.target.closest('.row').remove();
            }
        });
        fieldsDiv.addEventListener('change', function (e) {
            if (e.target.getAttribute('data-role') === 'type') {
                var optionsCol = e.target.closest('.row').querySelector('.options-col');
                if (e.target.value === 'select' || e.target.value === 'radio') {
                    optionsCol.classList.remove('d-none');
                } else {
                    optionsCol.classList.add('d-none');
                }
            }
        });
        document.getElementById('form-builder').addEventListener('submit', function () {
            var arr = [];
            fieldsDiv.querySelectorAll('.row').forEach(function (row) {
                arr.push({
                    label: row.querySelector('[data-role="label"]').value,
                    name: row.querySelector('[data-role="name"]').value,
                    type: row.querySelector('[data-role="type"]').value,
                    options: row.querySelector('[data-role="options"]').value
                });
            });
            fieldsInput.value = JSON.stringify(arr);
        });
    });
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
