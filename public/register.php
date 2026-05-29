<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';

    if ($password !== $password2) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        try {
            $user = new User();
            $user->register($username, $email, $password);
            setFlash('success', 'Account created! Please log in.');
            header('Location: login.php');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register – Password Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
<div class="card">
    <h1>🔐 Create Account</h1>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <label>Username
            <input type="text" name="username" required maxlength="50"
                   value="<?= h($_POST['username'] ?? '') ?>">
        </label>
        <label>E-mail
            <input type="email" name="email" required maxlength="150"
                   value="<?= h($_POST['email'] ?? '') ?>">
        </label>
        <label>Password
            <input type="password" name="password" required minlength="8">
        </label>
        <label>Confirm password
            <input type="password" name="password2" required minlength="8">
        </label>
        <button type="submit">Register</button>
    </form>
    <p class="link">Already have an account? <a href="login.php">Log in</a></p>
</div>
</body>
</html>
