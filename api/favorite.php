<?php
// ============================================
// NutriFit — API: Toggle Favorite (AJAX)
// ============================================
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'auth', 'message' => 'Debes iniciar sesión para guardar favoritos.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'method']);
    exit;
}

$recipeId = intval($_POST['recipe_id'] ?? 0);
if (!$recipeId) {
    echo json_encode(['error' => 'invalid']);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Check if already favorited
$stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = :uid AND recipe_id = :rid");
$stmt->execute([':uid' => $userId, ':rid' => $recipeId]);
$existing = $stmt->fetch();

if ($existing) {
    // Remove favorite
    $db->prepare("DELETE FROM favorites WHERE user_id = :uid AND recipe_id = :rid")
       ->execute([':uid' => $userId, ':rid' => $recipeId]);
    $favorited = false;
} else {
    // Add favorite
    $db->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (:uid, :rid)")
       ->execute([':uid' => $userId, ':rid' => $recipeId]);
    $favorited = true;
}

// Get total count for this recipe
$count = $db->prepare("SELECT COUNT(*) FROM favorites WHERE recipe_id = :rid");
$count->execute([':rid' => $recipeId]);
$totalFavs = intval($count->fetchColumn());

echo json_encode(['favorited' => $favorited, 'count' => $totalFavs, 'success' => true]);
