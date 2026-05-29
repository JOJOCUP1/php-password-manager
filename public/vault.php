<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$authUser  = requireLogin();
$masterKey = sessionMasterKey();
$vault     = new VaultEntry();
$entries   = $vault->getAllForUser($authUser['id'], $masterKey);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Vault – Password Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <span>🔐 Password Manager</span>
    <nav>
        <a href="generate.php">Generate password</a>
        <a href="change_password.php">Change password</a>
        <a href="logout.php">Logout (<?= h($authUser['username']) ?>)</a>
    </nav>
</header>

<main>
    <div class="vault-top">
        <h2>My Vault</h2>
        <a class="btn" href="entry_form.php">+ Add entry</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (empty($entries)): ?>
        <p class="empty">No saved passwords yet. <a href="entry_form.php">Add your first one.</a></p>
    <?php else: ?>
    <table class="vault-table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Password</th>
                <th>Saved on</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
            <tr>
                <td><?= h($e['service_name']) ?></td>
                <td>
                    <span class="pwd-mask" data-pwd="<?= h($e['password']) ?>">••••••••</span>
                    <button class="btn-sm reveal-btn" type="button">👁 Show</button>
                    <button class="btn-sm copy-btn"   type="button"
                            data-pwd="<?= h($e['password']) ?>">📋 Copy</button>
                </td>
                <td><?= h($e['created_at']) ?></td>
                <td>
                    <a class="btn-sm" href="entry_form.php?id=<?= $e['id'] ?>">Edit</a>
                    <a class="btn-sm danger"
                       href="entry_delete.php?id=<?= $e['id'] ?>"
                       onclick="return confirm('Delete this entry?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('.reveal-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const mask = btn.previousElementSibling;
        if (mask.textContent === '••••••••') {
            mask.textContent = mask.dataset.pwd;
            btn.textContent  = '🙈 Hide';
        } else {
            mask.textContent = '••••••••';
            btn.textContent  = '👁 Show';
        }
    });
});

document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.pwd).then(() => {
            const orig = btn.textContent;
            btn.textContent = '✅ Copied';
            setTimeout(() => btn.textContent = orig, 1500);
        });
    });
});
</script>
</body>
</html>
