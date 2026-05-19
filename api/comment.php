<?php
// ============================================
// NutriFit — API: Submit / Delete Comment (AJAX)
// ============================================
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'auth', 'message' => 'Debes iniciar sesión.']);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- DELETE ---
if ($action === 'delete') {
    $commentId = intval($_POST['comment_id'] ?? 0);
    $stmt = $db->prepare("SELECT user_id FROM recipe_comments WHERE id = :id");
    $stmt->execute([':id' => $commentId]);
    $row = $stmt->fetch();

    if (!$row || ($row['user_id'] != $userId && !isAdmin())) {
        echo json_encode(['error' => 'forbidden']);
        exit;
    }
    $db->prepare("DELETE FROM recipe_comments WHERE id = :id")->execute([':id' => $commentId]);
    echo json_encode(['success' => true, 'deleted' => $commentId]);
    exit;
}

// --- POST comment ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'method']);
    exit;
}

$recipeId = intval($_POST['recipe_id'] ?? 0);
$comment  = trim($_POST['comment'] ?? '');

if (!$recipeId || strlen($comment) < 3) {
    echo json_encode(['error' => 'invalid', 'message' => 'El comentario debe tener al menos 3 caracteres.']);
    exit;
}

if (strlen($comment) > 500) {
    echo json_encode(['error' => 'invalid', 'message' => 'El comentario no puede superar los 500 caracteres.']);
    exit;
}

// Rate-limit: max 5 comments per recipe per user
$cntStmt = $db->prepare("SELECT COUNT(*) FROM recipe_comments WHERE user_id = :uid AND recipe_id = :rid");
$cntStmt->execute([':uid' => $userId, ':rid' => $recipeId]);
if (intval($cntStmt->fetchColumn()) >= 5) {
    echo json_encode(['error' => 'limit', 'message' => 'Ya has dejado demasiados comentarios en esta receta.']);
    exit;
}

$insert = $db->prepare("INSERT INTO recipe_comments (user_id, recipe_id, comment) VALUES (:uid, :rid, :c)");
$insert->execute([':uid' => $userId, ':rid' => $recipeId, ':c' => $comment]);
$newId = $db->lastInsertId();

// Fetch the stored user info to return
$uStmt = $db->prepare("SELECT username FROM users WHERE id = :id");
$uStmt->execute([':id' => $userId]);
$username = $uStmt->fetchColumn();

echo json_encode([
    'success'  => true,
    'id'       => $newId,
    'comment'  => htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'),
    'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
    'date'     => date('d/m/Y H:i'),
    'own'      => true,
]);
