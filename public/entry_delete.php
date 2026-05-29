<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$authUser = requireLogin();
$entryId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $vault = new VaultEntry();
    $vault->delete($entryId, $authUser['id']);
    setFlash('success', 'Entry deleted.');
} catch (RuntimeException $e) {
    setFlash('danger', $e->getMessage());
}
header('Location: vault.php');
exit;
