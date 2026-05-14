<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if ($id) {
    $db = getDB();
    $db->prepare("DELETE FROM recipes WHERE id = ?")->execute([$id]);
    setFlash('success', 'Receta eliminada correctamente.');
}
header("Location: recipes.php");
exit;
