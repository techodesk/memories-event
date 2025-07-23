<?php
session_start();
$config = require __DIR__ . '/../config/config.php';

// Connect to event-manager DB
$dbConf = $config['db_event_manager'];
$dsn = "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset={$dbConf['charset']}";
$pdo = new PDO($dsn, $dbConf['user'], $dbConf['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Fetch user + role info
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name, r.permissions
        FROM staff_users u
        LEFT JOIN staff_roles r ON u.role_id = r.id
        WHERE u.username = :username OR u.email = :username
        LIMIT 1
    ");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['is_active']) {
        $error = 'User not found or inactive.';
    } elseif (!password_verify($password, $user['password_hash'])) {
        $error = 'Incorrect password.';
    } else {
        // Save user info to session
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['display_name']  = $user['display_name'];
        $_SESSION['role_id']       = $user['role_id'];
        $_SESSION['role_name']     = $user['role_name'];
        $_SESSION['permissions']   = $user['permissions'] ? json_decode($user['permissions'], true) : [];

        // Update last_login
        $pdo->prepare("UPDATE staff_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        header('Location: dashboard');
        exit;
    }
}
$page_title = 'Staff Login';
include __DIR__ . '/../templates/header.php';
?>

<div class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="p-4" style="background: var(--card-bg); border-radius: var(--border-radius); box-shadow: 0 4px 24px #0001; min-width:320px;">
        <h2 class="text-center mb-3">Staff Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger text-center py-2 mb-3">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username or Email" required autofocus>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-accent w-100">Login</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
