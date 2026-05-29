<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$authUser  = requireLogin();
$masterKey = sessionMasterKey();
$vault     = new VaultEntry();

$entryId  = isset($_GET['id']) ? (int) $_GET['id'] : null;
$existing = null;
$error    = '';

if ($entryId !== null) {
    try {
        $existing = $vault->getOne($entryId, $authUser['id'], $masterKey);
    } catch (RuntimeException $e) {
        setFlash('danger', $e->getMessage());
        header('Location: vault.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceName = trim($_POST['service_name'] ?? '');
    $password    = $_POST['password']          ?? '';
    $notes       = trim($_POST['notes']        ?? '');

    if ($serviceName === '' || $password === '') {
        $error = 'Service name and password are required.';
    } else {
        try {
            if ($entryId === null) {
                $vault->create($authUser['id'], $masterKey, $serviceName, $password, $notes);
                setFlash('success', 'Entry saved.');
            } else {
                $vault->update($entryId, $authUser['id'], $masterKey, $serviceName, $password, $notes);
                setFlash('success', 'Entry updated.');
            }
            header('Location: vault.php');
            exit;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$title    = $entryId === null ? 'Add Entry' : 'Edit Entry';
$defSvc   = h($_POST['service_name'] ?? $existing['service_name'] ?? '');
$defPwd   = h($_POST['password']     ?? $existing['password']     ?? '');
$defNotes = h($_POST['notes']        ?? $existing['notes']        ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> – Password Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
<div class="card wide">
    <h2><?= h($title) ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Service / Website name
            <input type="text" name="service_name" required maxlength="100"
                   value="<?= $defSvc ?>">
        </label>
        <label>Password
            <div class="pwd-row">
                <input type="text" name="password" id="pwd-field" required
                       value="<?= $defPwd ?>">
                <button type="button" class="btn-sm" id="gen-btn">⚡ Generate</button>
            </div>
        </label>
        <details>
            <summary>Generator options</summary>
            <div class="gen-opts">
                <label>Length <input type="number" id="g-len" value="12" min="4" max="128"></label>
                <label>Lowercase <input type="number" id="g-low" value="3" min="0"></label>
                <label>Uppercase <input type="number" id="g-up"  value="3" min="0"></label>
                <label>Digits <input type="number" id="g-dig" value="3" min="0"></label>
                <label>Specials <input type="number" id="g-sp"  value="3" min="0"></label>
                <label><input type="checkbox" id="g-pct"> Use % instead of units</label>
            </div>
        </details>
        <label>Notes (optional)
            <textarea name="notes" rows="3"><?= $defNotes ?></textarea>
        </label>
        <div class="form-actions">
            <button type="submit">Save</button>
            <a href="vault.php" class="btn secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function randInt(min, max) {
    const range = max - min + 1;
    const arr   = new Uint32Array(1);
    let result;
    do {
        crypto.getRandomValues(arr);
        result = arr[0] % range;
    } while (arr[0] - result + (range - 1) < arr[0]);
    return min + result;
}

function pickChars(charset, count) {
    let out = '';
    for (let i = 0; i < count; i++) out += charset[randInt(0, charset.length - 1)];
    return out;
}

function shuffleStr(str) {
    const arr = str.split('');
    for (let i = arr.length - 1; i > 0; i--) {
        const j = randInt(0, i);
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr.join('');
}

document.getElementById('gen-btn').addEventListener('click', () => {
    const LOWER   = 'abcdefghijklmnopqrstuvwxyz';
    const UPPER   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const DIGITS  = '0123456789';
    const SPECIAL = '!@#$%^&*()-_=+[]{}|;:,.<>?';

    let length = Math.max(4, parseInt(document.getElementById('g-len').value) || 12);
    let low    = parseInt(document.getElementById('g-low').value) || 0;
    let up     = parseInt(document.getElementById('g-up').value)  || 0;
    let dig    = parseInt(document.getElementById('g-dig').value) || 0;
    let sp     = parseInt(document.getElementById('g-sp').value)  || 0;
    const pct  = document.getElementById('g-pct').checked;

    if (pct) {
        low = Math.round(length * low    / 100);
        up  = Math.round(length * up     / 100);
        dig = Math.round(length * dig    / 100);
        sp  = Math.round(length * sp     / 100);
    }

    if (low + up + dig + sp === 0) { low = up = dig = sp = Math.ceil(length / 4); }

    let extra = length - (low + up + dig + sp);
    if (extra < 0) extra = 0;
    low += extra;

    const pwd = shuffleStr(
        pickChars(LOWER, low) +
        pickChars(UPPER, up)  +
        pickChars(DIGITS, dig) +
        pickChars(SPECIAL, sp)
    );
    document.getElementById('pwd-field').value = pwd;
});
</script>
</body>
</html>
