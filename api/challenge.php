<?php
// ============================================
// NutriFit — API: Challenge Actions (AJAX)
// ============================================
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'auth']);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$key    = trim($_POST['challenge_key'] ?? '');

$validKeys = ['vegan_week','low_cal_snack','protein_boost','explore_five','plan_generator','breakfast_streak'];

if (!in_array($key, $validKeys)) {
    echo json_encode(['error' => 'invalid_key']);
    exit;
}

if ($action === 'join') {
    try {
        $db->prepare("INSERT IGNORE INTO user_challenges (user_id, challenge_key) VALUES (:uid, :key)")
           ->execute([':uid' => $userId, ':key' => $key]);
        echo json_encode(['success' => true, 'joined' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'progress') {
    $inc = intval($_POST['increment'] ?? 1);
    // Get current
    $stmt = $db->prepare("SELECT progress, completed FROM user_challenges WHERE user_id = :uid AND challenge_key = :key");
    $stmt->execute([':uid' => $userId, ':key' => $key]);
    $row = $stmt->fetch();
    if (!$row) { echo json_encode(['error' => 'not_joined']); exit; }
    if ($row['completed']) { echo json_encode(['success' => true, 'completed' => true, 'progress' => $row['progress']]); exit; }

    $targets = ['vegan_week'=>7,'low_cal_snack'=>3,'protein_boost'=>5,'explore_five'=>5,'plan_generator'=>1,'breakfast_streak'=>7];
    $target = $targets[$key] ?? 5;
    $newProgress = min($row['progress'] + $inc, $target);
    $completed = ($newProgress >= $target) ? 1 : 0;
    $completedAt = $completed ? date('Y-m-d H:i:s') : null;

    $upd = $db->prepare("UPDATE user_challenges SET progress = :p, completed = :c, completed_at = :ca WHERE user_id = :uid AND challenge_key = :key");
    $upd->execute([':p' => $newProgress, ':c' => $completed, ':ca' => $completedAt, ':uid' => $userId, ':key' => $key]);

    echo json_encode(['success' => true, 'progress' => $newProgress, 'target' => $target, 'completed' => (bool)$completed]);
    exit;
}

echo json_encode(['error' => 'unknown_action']);
