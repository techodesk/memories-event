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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Login – Event Manager</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background: #f5f5f5; font-family: sans-serif; }
        .login-container { max-width: 350px; margin: 6em auto; background: #fff; padding: 2em 2.5em 2em 2.5em; border-radius: 12px; box-shadow: 0 4px 24px #0001; }
        .login-container h2 { text-align: center; }
        .error { background: #ffd7d7; color: #a00; padding: 1em; margin-bottom: 1em; border-radius: 6px; text-align: center; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 0.7em 0; border: 1px solid #ccc; border-radius: 4px; }
        button[type="submit"] { width: 100%; padding: 10px; background: #673ab7; color: #fff; border: none; border-radius: 4px; font-weight: bold; }
        button[type="submit"]:hover { background: #512da8; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Staff Login</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <input type="text" name="username" placeholder="Username or Email" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
