<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';

    try {
        $userModel = new User();
        $userData  = $userModel->login($username, $password);

        $_SESSION['user'] = [
            'id'             => $userData['id'],
            'username'       => $userData['username'],
            'email'          => $userData['email'],
            'master_key_b64' => base64_encode($userData['master_key']),
        ];

        header('Location: vault.php');
        exit;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login – Password Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
<div class="card">
    <h1>🔐 Password Manager</h1>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Username
            <input type="text" name="username" required autofocus
                   value="<?= h($_POST['username'] ?? '') ?>">
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit">Log in</button>
    </form>
    <p class="link">No account? <a href="register.php">Register</a></p>
</div>
</body>
</html>
