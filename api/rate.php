<?php
// ============================================
// NutriFit — API: Rate Recipe (AJAX)
// ============================================
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'auth', 'message' => 'Debes iniciar sesión para valorar.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'method']);
    exit;
}

$recipeId = intval($_POST['recipe_id'] ?? 0);
$rating   = intval($_POST['rating'] ?? 0);

if (!$recipeId || $rating < 1 || $rating > 5) {
    echo json_encode(['error' => 'invalid']);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Upsert rating
$stmt = $db->prepare("INSERT INTO recipe_ratings (user_id, recipe_id, rating)
    VALUES (:uid, :rid, :r)
    ON DUPLICATE KEY UPDATE rating = :r2");
$stmt->execute([':uid' => $userId, ':rid' => $recipeId, ':r' => $rating, ':r2' => $rating]);

// Get new average and count
$avg = $db->prepare("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM recipe_ratings WHERE recipe_id = :rid");
$avg->execute([':rid' => $recipeId]);
$data = $avg->fetch();

echo json_encode([
    'success'     => true,
    'your_rating' => $rating,
    'avg'         => round(floatval($data['avg']), 1),
    'count'       => intval($data['cnt']),
]);
