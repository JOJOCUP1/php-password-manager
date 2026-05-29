<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$authUser = requireLogin();
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPwd  = $_POST['old_password']  ?? '';
    $newPwd  = $_POST['new_password']  ?? '';
    $newPwd2 = $_POST['new_password2'] ?? '';

    if ($newPwd !== $newPwd2) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPwd) < 8) {
        $error = 'New password must be at least 8 characters.';
    } else {
        try {
            $user = new User();
            $user->changePassword($authUser['id'], $oldPwd, $newPwd);

            session_destroy();
            session_start();
            setFlash('success', 'Password changed. Please log in again.');
            header('Location: login.php');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password – Password Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
<div class="card">
    <h2>Change Login Password</h2>
    <p class="note">⚠️ Changing your password re-encrypts your master key. All vault data remains intact.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Current password
            <input type="password" name="old_password" required>
        </label>
        <label>New password
            <input type="password" name="new_password" required minlength="8">
        </label>
        <label>Confirm new password
            <input type="password" name="new_password2" required minlength="8">
        </label>
        <div class="form-actions">
            <button type="submit">Change password</button>
            <a href="vault.php" class="btn secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
