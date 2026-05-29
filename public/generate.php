<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$authUser  = requireLogin();
$generated = '';
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $length  = (int) ($_POST['length']   ?? 12);
    $lower   = (int) ($_POST['lower']    ?? 0);
    $upper   = (int) ($_POST['upper']    ?? 0);
    $digits  = (int) ($_POST['digits']   ?? 0);
    $special = (int) ($_POST['special']  ?? 0);
    $usePct  = isset($_POST['use_pct']);

    try {
        $gen       = new PasswordGenerator();
        $generated = $gen->generate($length, $lower, $upper, $special, $digits, $usePct);
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generate Password – Password Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
<div class="card wide">
    <h2>⚡ Password Generator</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($generated !== ''): ?>
        <div class="generated-pwd">
            <span id="gen-out"><?= h($generated) ?></span>
            <button type="button" class="btn-sm"
                    onclick="navigator.clipboard.writeText(document.getElementById('gen-out').textContent)">
                📋 Copy
            </button>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Total length
            <input type="number" name="length" value="<?= (int)($_POST['length'] ?? 12) ?>"
                   min="4" max="128" required>
        </label>
        <label>Lowercase letters
            <input type="number" name="lower" value="<?= (int)($_POST['lower'] ?? 3) ?>" min="0">
        </label>
        <label>Uppercase letters
            <input type="number" name="upper" value="<?= (int)($_POST['upper'] ?? 3) ?>" min="0">
        </label>
        <label>Digits
            <input type="number" name="digits" value="<?= (int)($_POST['digits'] ?? 3) ?>" min="0">
        </label>
        <label>Special characters
            <input type="number" name="special" value="<?= (int)($_POST['special'] ?? 3) ?>" min="0">
        </label>
        <label>
            <input type="checkbox" name="use_pct" <?= isset($_POST['use_pct']) ? 'checked' : '' ?>>
            Treat counts as percentages
        </label>
        <button type="submit">Generate</button>
    </form>
    <p class="link"><a href="vault.php">← Back to vault</a></p>
</div>
</body>
</html>
