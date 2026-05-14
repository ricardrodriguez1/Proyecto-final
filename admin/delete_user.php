<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if ($id && $id != $_SESSION['user_id']) {
    $db = getDB();
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    setFlash('success', 'Usuario eliminado correctamente.');
}
header("Location: users.php");
exit;
