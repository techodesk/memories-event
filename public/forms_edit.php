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

$formId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$formId) {
    die('Form ID missing');
}

// Fetch form
$stmt = $pdo->prepare('SELECT * FROM forms WHERE id=?');
$stmt->execute([$formId]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    die('Form not found');
}

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM form_submissions WHERE form_id=?')->execute([$formId]);
    $pdo->prepare('DELETE FROM forms WHERE id=?')->execute([$formId]);
    header('Location: forms_admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $fields = $_POST['fields'] ?? '[]';
    $stmt = $pdo->prepare('UPDATE forms SET name=?, fields=? WHERE id=?');
    $stmt->execute([$_POST['name'], $fields, $formId]);
    header('Location: forms_admin.php');
    exit;
}

$fields = json_decode($form['fields'], true) ?: [];

$page_title = 'Edit Form';
$is_staff = true;
$display_name = $_SESSION['display_name'] ?? $_SESSION['username'] ?? '';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include __DIR__ . '/../templates/topbar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-section mb-4">
        <h2 class="mb-3 section-title">Edit Form</h2>
        <form method="post" id="form-builder" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($form['name']) ?>" required>
            </div>
            <div id="fields"></div>
            <button type="button" id="add-field" class="btn btn-secondary btn-sm mb-3">Add Field</button>
            <input type="hidden" name="fields" id="fields-input">
            <button type="submit" class="btn btn-accent">Save</button>
            <a href="forms_edit.php?id=<?= $formId ?>&delete=1" class="btn btn-danger ms-2" onclick="return confirm('Delete this form?')"><i class="bi bi-trash"></i></a>
        </form>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var fieldsDiv = document.getElementById('fields');
        var fieldsInput = document.getElementById('fields-input');
        var existing = <?php echo json_encode($fields); ?>;
        existing.forEach(function (f) {
            addRow(f);
        });
        document.getElementById('add-field').addEventListener('click', function () {
            addRow();
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

        function addRow(field) {
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
            if (field) {
                row.querySelector('[data-role="label"]').value = field.label || '';
                row.querySelector('[data-role="name"]').value = field.name || '';
                row.querySelector('[data-role="type"]').value = field.type || 'text';
                row.querySelector('[data-role="options"]').value = field.options || '';
                if (field.type === 'select' || field.type === 'radio') {
                    row.querySelector('.options-col').classList.remove('d-none');
                }
            }
        }
    });
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
