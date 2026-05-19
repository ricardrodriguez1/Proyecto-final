<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Fetch current user details
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmtUser->execute([':id' => $userId]);
$user = $stmtUser->fetch();

if (!$user) {
    // Session user doesn't exist in DB anymore
    session_destroy();
    redirect('/auth/login.php');
}

$isAdmin = ($user['role'] === 'admin');

// --- HANDLE POST ACTIONS ---

// 1. Delete a requested plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_plan') {
    $planId = intval($_POST['plan_id'] ?? 0);
    
    // Safety check: ensure it belongs to the user
    $stmtCheck = $db->prepare("SELECT id FROM user_plans WHERE id = :id AND user_id = :uid");
    $stmtCheck->execute([':id' => $planId, ':uid' => $userId]);
    
    if ($stmtCheck->fetch()) {
        $stmtDel = $db->prepare("DELETE FROM user_plans WHERE id = :id");
        $stmtDel->execute([':id' => $planId]);
        setFlash('success', 'El plan ha sido eliminado de tu historial con éxito.');
    } else {
        setFlash('error', 'No tienes permisos para eliminar este plan o no existe.');
    }
    redirect('/profile.php');
}

// 2. Update user profile details (email / password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($email)) {
        setFlash('error', 'El correo electrónico no puede estar vacío.');
    } else {
        // Check if email already used by another user
        $stmtEmail = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :uid");
        $stmtEmail->execute([':email' => $email, ':uid' => $userId]);
        if ($stmtEmail->fetch()) {
            setFlash('error', 'El correo electrónico ya está en uso.');
        } else {
            // Password change flow
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    setFlash('error', 'Introduce tu contraseña actual para cambiarla.');
                } elseif (!password_verify($currentPassword, $user['password'])) {
                    setFlash('error', 'La contraseña actual es incorrecta.');
                } elseif ($newPassword !== $confirmPassword) {
                    setFlash('error', 'La nueva contraseña y la de confirmación no coinciden.');
                } elseif (strlen($newPassword) < 6) {
                    setFlash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmtUp = $db->prepare("UPDATE users SET email = :email, password = :password WHERE id = :uid");
                    $stmtUp->execute([':email' => $email, ':password' => $hashed, ':uid' => $userId]);
                    setFlash('success', '¡Perfil y contraseña actualizados correctamente!');
                    redirect('/profile.php');
                }
            } else {
                // Email update only
                $stmtUp = $db->prepare("UPDATE users SET email = :email WHERE id = :uid");
                $stmtUp->execute([':email' => $email, ':uid' => $userId]);
                setFlash('success', '¡Correo electrónico actualizado correctamente!');
                redirect('/profile.php');
            }
        }
    }
}

// --- FETCH DATA FOR VIEWS ---

$labelActivity = [
    'sedentario'   => 'Sedentario (Sin ejercicio)',
    'ligero'       => 'Ligero (1-3 días/sem)',
    'moderado'     => 'Moderado (3-5 days/sem)',
    'activo'       => 'Activo (6-7 days/sem)',
    'muy_activo'   => 'Muy Activo (Atleta/Físico)',
];

$labelObjective = [
    'volumen'      => 'Volumen (+15% Kcal)',
    'mantenimiento'=> 'Mantenimiento (Equilibrado)',
    'definicion'   => 'Definición (-15% Kcal)',
];

$labelDiet = [
    'omnivoro'     => 'Omnívora 🥩',
    'vegetariano'  => 'Vegetariana 🥚',
    'vegano'       => 'Vegana 🌱',
];

if (!$isAdmin) {
    // Normal User Data
    // Get user's latest plan for health profile dashboard
    $stmtLastPlan = $db->prepare("SELECT * FROM user_plans WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1");
    $stmtLastPlan->execute([':uid' => $userId]);
    $lastPlan = $stmtLastPlan->fetch();

    // Calculate BMI and health metrics if last plan exists
    $bmi = null;
    $bmiStatus = '';
    $bmiClass = '';
    $bmiPercent = 0;
    if ($lastPlan) {
        $weight = floatval($lastPlan['peso']);
        $heightM = floatval($lastPlan['altura']) / 100;
        if ($heightM > 0) {
            $bmi = round($weight / ($heightM * $heightM), 1);
            if ($bmi < 18.5) {
                $bmiStatus = 'Bajo peso';
                $bmiClass = 'text-warning';
                $bmiPercent = ($bmi / 40) * 100;
            } elseif ($bmi < 25) {
                $bmiStatus = 'Peso saludable';
                $bmiClass = 'text-success';
                $bmiPercent = ($bmi / 40) * 100;
            } elseif ($bmi < 30) {
                $bmiStatus = 'Sobrepeso';
                $bmiClass = 'text-warning';
                $bmiPercent = ($bmi / 40) * 100;
            } else {
                $bmiStatus = 'Obesidad';
                $bmiClass = 'text-danger';
                $bmiPercent = min(($bmi / 40) * 100, 100);
            }
        }
    }

    // Get user's plan history (detailed)
    $stmtAllPlans = $db->prepare("SELECT up.*, 
        rb.title AS breakfast_title, rb.image_url AS breakfast_image, rb.calories AS breakfast_calories,
        rl.title AS lunch_title, rl.image_url AS lunch_image, rl.calories AS lunch_calories,
        rd.title AS dinner_title, rd.image_url AS dinner_image, rd.calories AS dinner_calories,
        rs.title AS snack_title, rs.image_url AS snack_image, rs.calories AS snack_calories
        FROM user_plans up
        LEFT JOIN recipes rb ON up.breakfast_id = rb.id
        LEFT JOIN recipes rl ON up.lunch_id = rl.id
        LEFT JOIN recipes rd ON up.dinner_id = rd.id
        LEFT JOIN recipes rs ON up.snack_id = rs.id
        WHERE up.user_id = :uid 
        ORDER BY up.created_at DESC");
    $stmtAllPlans->execute([':uid' => $userId]);
    $allPlans = $stmtAllPlans->fetchAll();
    $totalUserPlans = count($allPlans);

    // Fetch user's favorite recipes
    $stmtMyFavs = $db->prepare("SELECT r.* FROM recipes r JOIN favorites f ON r.id = f.recipe_id WHERE f.user_id = :uid ORDER BY f.created_at DESC");
    $stmtMyFavs->execute([':uid' => $userId]);
    $myFavorites = $stmtMyFavs->fetchAll();
    $totalFavorites = count($myFavorites);
} else {
    // Admin Dashboard Data
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalRecipes = $db->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
    $totalSavedPlans = $db->query("SELECT COUNT(*) FROM user_plans")->fetchColumn();

    // Latest active registered users
    $recentUsers = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 8")->fetchAll();

    // Community calculator activity: what plans are users generating recently?
    $stmtRecentPlansAdmin = $db->query("SELECT up.*, u.username, u.email 
        FROM user_plans up 
        JOIN users u ON up.user_id = u.id 
        ORDER BY up.created_at DESC LIMIT 10");
    $recentPlansAdmin = $stmtRecentPlansAdmin->fetchAll();
}

$pageTitle = $isAdmin ? 'Perfil del Administrador' : 'Mi Perfil';
include __DIR__ . '/includes/header.php';
?>

<!-- Premium styling block for high visual aesthetics -->
<style>
    /* Profile Grid Setup */
    .profile-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 32px;
        margin-top: 30px;
        margin-bottom: 60px;
    }
    
    @media (max-width: 992px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Left Sidebar Styling */
    .profile-sidebar {
        background: var(--bg-card);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius);
        padding: 40px 24px;
        text-align: center;
        height: fit-content;
        position: sticky;
        top: calc(var(--nav-height) + 20px);
        backdrop-filter: blur(10px);
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: var(--gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        color: #fff;
        margin: 0 auto 20px;
        box-shadow: var(--shadow-glow);
    }
    
    .profile-username {
        font-size: 1.4rem;
        font-weight: 800;
        margin-bottom: 6px;
        letter-spacing: -0.5px;
    }
    
    .profile-email {
        font-size: 0.88rem;
        color: var(--text-secondary);
        margin-bottom: 20px;
        word-break: break-all;
    }
    
    .profile-role-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 30px;
    }
    
    .badge-user {
        background: rgba(6, 182, 212, 0.12);
        color: var(--accent2);
        border: 1px solid rgba(6, 182, 212, 0.25);
    }
    
    .badge-admin {
        background: rgba(245, 158, 11, 0.12);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.25);
        box-shadow: 0 0 15px rgba(245, 158, 11, 0.08);
    }
    
    .profile-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        text-align: left;
    }
    
    .profile-menu-item {
        margin-bottom: 8px;
    }
    
    .profile-menu-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-radius: var(--radius-sm);
        color: var(--text-secondary) !important;
        font-weight: 600;
        font-size: 0.92rem;
        transition: var(--transition);
        border: 1px solid transparent !important;
        cursor: pointer;
        background: transparent !important;
        width: 100%;
        text-align: left;
        outline: none !important;
        box-shadow: none !important;
    }
    
    .profile-menu-link:hover {
        background: var(--bg-card-hover) !important;
        color: var(--text-primary) !important;
    }
    
    .profile-menu-link.active {
        background: rgba(16, 185, 129, 0.15) !important;
        color: var(--accent-light) !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }
    
    /* Right Dashboard Styling */
    .dashboard-panel {
        display: none;
        animation: panelFade 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    .dashboard-panel.active {
        display: block;
    }
    
    @keyframes panelFade {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Executive Summary Cards */
    .summary-title {
        font-size: 1.6rem;
        font-weight: 800;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .summary-subtitle {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin-bottom: 30px;
    }
    
    .health-profile-card {
        background: var(--bg-card);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius);
        padding: 32px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    
    .health-profile-card::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 150px; height: 150px;
        background: radial-gradient(circle, rgba(6, 182, 212, 0.08) 0%, transparent 70%);
        pointer-events: none;
    }
    
    /* Health Grid */
    .health-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 16px;
        margin-top: 24px;
    }
    
    .health-item {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius-sm);
        padding: 16px;
        text-align: center;
        transition: var(--transition);
    }
    
    .health-item:hover {
        background: rgba(255, 255, 255, 0.04);
        transform: translateY(-2px);
    }
    
    .health-label {
        font-size: 0.72rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    
    .health-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-primary);
    }
    
    .health-value span {
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    /* BMI Bar styling */
    .bmi-section {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius-sm);
        padding: 24px;
        margin-top: 24px;
    }
    
    .bmi-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    
    .bmi-score-box {
        display: flex;
        align-items: baseline;
        gap: 8px;
    }
    
    .bmi-num {
        font-size: 2.2rem;
        font-weight: 900;
        background: var(--gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .bmi-lbl {
        font-size: 0.95rem;
        font-weight: 700;
    }
    
    .bmi-status-badge {
        font-size: 0.8rem;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
    }
    
    .bmi-underweight { background: rgba(245, 158, 11, 0.12); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2); }
    .bmi-healthy { background: rgba(16, 185, 129, 0.12); color: var(--accent-light); border: 1px solid rgba(16, 185, 129, 0.2); }
    .bmi-overweight { background: rgba(245, 158, 11, 0.12); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2); }
    .bmi-obese { background: rgba(239, 68, 68, 0.12); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
    
    .bmi-track {
        height: 8px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        position: relative;
        margin: 16px 0 8px;
    }
    
    .bmi-pointer {
        height: 100%;
        border-radius: 10px;
        background: var(--gradient);
        transition: width 1.2s cubic-bezier(0.1, 0.8, 0.2, 1);
    }
    
    .bmi-axis {
        display: flex;
        justify-content: space-between;
        font-size: 0.72rem;
        color: var(--text-muted);
        font-weight: 600;
    }
    
    /* Plan History Cards */
    .history-card {
        background: var(--bg-card);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius);
        padding: 28px;
        margin-bottom: 24px;
        transition: var(--transition);
        position: relative;
    }
    
    .history-card:hover {
        border-color: rgba(16, 185, 129, 0.2);
        box-shadow: var(--shadow-glow);
    }
    
    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-glass);
        padding-bottom: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }
    
    .history-date {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .history-cal-box {
        text-align: right;
    }
    
    .history-calories {
        font-size: 1.5rem;
        font-weight: 900;
        color: var(--accent-light);
    }
    
    .history-goal {
        font-size: 0.78rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    
    .history-bio-summary {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    
    .history-badge {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-glass);
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .history-badge strong {
        color: var(--text-primary);
    }
    
    /* Plan Meal Grid */
    .plan-meals-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 16px;
    }
    
    .plan-meal-item {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius-sm);
        padding: 10px;
        transition: var(--transition);
        text-decoration: none;
        color: inherit;
    }
    
    .plan-meal-item:hover {
        background: var(--bg-card-hover);
        border-color: rgba(16, 185, 129, 0.15);
        color: inherit;
        transform: translateY(-2px);
    }
    
    .plan-meal-thumb {
        width: 54px;
        height: 54px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
        background: var(--bg-secondary);
    }
    
    .plan-meal-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .plan-meal-details {
        overflow: hidden;
    }
    
    .plan-meal-category {
        font-size: 0.68rem;
        color: var(--accent);
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 0.5px;
    }
    
    .plan-meal-title {
        font-size: 0.85rem;
        font-weight: 700;
        margin: 2px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .plan-meal-calories {
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    
    .plan-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
        border-top: 1px solid rgba(255, 255, 255, 0.03);
        padding-top: 16px;
    }
    
    /* Empty State */
    .empty-state {
        background: var(--bg-card);
        border: 1.5px dashed var(--border-glass);
        border-radius: var(--radius);
        padding: 60px 40px;
        text-align: center;
        backdrop-filter: blur(10px);
    }
    
    .empty-state i {
        font-size: 3rem;
        color: var(--text-muted);
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .empty-state p {
        color: var(--text-secondary);
        font-size: 0.92rem;
        margin-bottom: 24px;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Admin User Table */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border: 1px solid var(--border-glass);
        border-radius: var(--radius-sm);
        background: var(--bg-card);
        margin-top: 20px;
    }
    
    .table-fancy {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    
    .table-fancy th, .table-fancy td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-glass);
        font-size: 0.88rem;
    }
    
    .table-fancy th {
        background: rgba(255, 255, 255, 0.02);
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    .table-fancy tbody tr:last-child td {
        border-bottom: none;
    }
    
    .table-fancy tbody tr:hover {
        background: rgba(255, 255, 255, 0.015);
    }
    
    /* Stats Layout */
    .admin-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .admin-stat-box {
        background: var(--bg-card);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius);
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: var(--transition);
    }
    
    .admin-stat-box:hover {
        transform: translateY(-3px);
        background: var(--bg-card-hover);
        box-shadow: var(--shadow-glow);
    }
    
    .admin-stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .admin-stat-icon.green { background: rgba(16, 185, 129, 0.1); color: var(--accent); }
    .admin-stat-icon.blue { background: rgba(6, 182, 212, 0.1); color: var(--accent2); }
    .admin-stat-icon.orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
    
    .admin-stat-num {
        font-size: 1.8rem;
        font-weight: 950;
        color: var(--text-primary);
        line-height: 1.1;
    }
    
    .admin-stat-lbl {
        font-size: 0.8rem;
        color: var(--text-secondary);
        font-weight: 600;
        margin-top: 4px;
    }
    
    /* Form Glass Panel */
    .form-panel {
        background: var(--bg-card);
        border: 1px solid var(--border-glass);
        border-radius: var(--radius);
        padding: 36px;
        max-width: 600px;
    }
    
    .form-separator {
        height: 1px;
        background: var(--border-glass);
        margin: 30px 0;
    }
    
    /* Print Utility */
    @media print {
        body { background: #fff !important; color: #000 !important; }
        .navbar, .profile-sidebar, .profile-menu, .plan-actions, .no-print, .flash { display: none !important; }
        .profile-grid { grid-template-columns: 1fr !important; margin: 0 !important; }
        .main-content { padding-top: 0 !important; }
        .history-card { border: 1px solid #000 !important; background: #fff !important; color: #000 !important; page-break-inside: avoid; }
        .plan-meal-item { border: 1px solid #ddd !important; background: #fff !important; color: #000 !important; }
        .plan-meal-item img { -webkit-print-color-adjust: exact; }
    }
</style>

<div class="profile-page container">
    <div class="profile-grid">
        
        <!-- SIDEBAR -->
        <aside class="profile-sidebar animate-in">
            <div class="profile-avatar">
                <i class="fas <?php echo $isAdmin ? 'fa-user-shield' : 'fa-user'; ?>"></i>
            </div>
            
            <h3 class="profile-username"><?php echo sanitize($user['username']); ?></h3>
            <p class="profile-email"><?php echo sanitize($user['email']); ?></p>
            
            <span class="profile-role-badge <?php echo $isAdmin ? 'badge-admin' : 'badge-user'; ?>">
                <i class="fas <?php echo $isAdmin ? 'fa-shield-alt' : 'fa-user'; ?>" style="margin-right:6px"></i>
                <?php echo $isAdmin ? 'Administrador' : 'Usuario'; ?>
            </span>
            
            <!-- Dynamic interactive tabs -->
            <ul class="profile-menu">
                <?php if (!$isAdmin): ?>
                    <!-- Normal User Tabs -->
                    <li class="profile-menu-item">
                        <button class="profile-menu-link active" onclick="switchTab('dashboard')">
                            <i class="fas fa-chart-bar" style="width:20px"></i> Mi Panel
                        </button>
                    </li>
                    <li class="profile-menu-item">
                        <button class="profile-menu-link" onclick="switchTab('plans')">
                            <i class="fas fa-clipboard-list" style="width:20px"></i> Mis Planes (<?php echo $totalUserPlans; ?>)
                        </button>
                    </li>
                    <li class="profile-menu-item">
                        <button class="profile-menu-link" onclick="switchTab('favorites')">
                            <i class="fas fa-heart" style="width:20px"></i> Mis Favoritos (<?php echo $totalFavorites; ?>)
                        </button>
                    </li>
                <?php else: ?>
                    <!-- Admin Tabs -->
                    <li class="profile-menu-item">
                        <button class="profile-menu-link active" onclick="switchTab('admin-dashboard')">
                            <i class="fas fa-tachometer-alt" style="width:20px"></i> Panel Admin
                        </button>
                    </li>
                    <li class="profile-menu-item">
                        <button class="profile-menu-link" onclick="switchTab('admin-community')">
                            <i class="fas fa-users-cog" style="width:20px"></i> Actividad Web
                        </button>
                    </li>
                    <li class="profile-menu-item">
                        <button class="profile-menu-link" onclick="switchTab('admin-users')">
                            <i class="fas fa-users" style="width:20px"></i> Usuarios
                        </button>
                    </li>
                <?php endif; ?>
                
                <li class="profile-menu-item">
                    <button class="profile-menu-link" onclick="switchTab('settings')">
                        <i class="fas fa-cog" style="width:20px"></i> Mi Cuenta
                    </button>
                </li>
                
                <li class="profile-menu-item" style="margin-top:20px; border-top: 1px solid var(--border-glass); padding-top:16px">
                    <a href="<?php echo baseUrl(); ?>/auth/logout.php" class="profile-menu-link" style="color:var(--danger)">
                        <i class="fas fa-sign-out-alt" style="width:20px"></i> Cerrar sesión
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- MAIN DASHBOARD PANELS -->
        <main class="profile-main-content">
            
            <?php if (!$isAdmin): ?>
                <!-- ==================== NORMAL USER PANELS ==================== -->
                
                <!-- Tab 1: User Dashboard -->
                <section id="panel-dashboard" class="dashboard-panel active">
                    <div class="summary-header">
                        <h2 class="summary-title"><i class="fas fa-chart-line" style="color:var(--accent)"></i> Tu Resumen de Bienestar</h2>
                        <p class="summary-subtitle">Análisis basado en tus datos metabólicos y objetivos actuales.</p>
                    </div>
                    
                    <?php if ($lastPlan): ?>
                        <div class="health-profile-card animate-in">
                            <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:12px"><i class="fas fa-heartbeat" style="color:var(--accent2)"></i> Perfil de Salud Generado</h3>
                            <p style="color:var(--text-secondary);font-size:0.9rem">
                                Basado en los datos de tu última solicitud el <strong><?php echo date('d/m/Y H:i', strtotime($lastPlan['created_at'])); ?></strong>.
                            </p>
                            
                            <!-- Biometrics grid -->
                            <div class="health-grid">
                                <div class="health-item">
                                    <div class="health-label">Género</div>
                                    <div class="health-value"><?php echo $lastPlan['genero'] === 'male' ? 'Hombre ♂' : 'Mujer ♀'; ?></div>
                                </div>
                                <div class="health-item">
                                    <div class="health-label">Edad</div>
                                    <div class="health-value"><?php echo sanitize($lastPlan['edad']); ?> <span>años</span></div>
                                </div>
                                <div class="health-item">
                                    <div class="health-label">Peso Actual</div>
                                    <div class="health-value"><?php echo sanitize($lastPlan['peso']); ?> <span>kg</span></div>
                                </div>
                                <div class="health-item">
                                    <div class="health-label">Altura</div>
                                    <div class="health-value"><?php echo sanitize($lastPlan['altura']); ?> <span>cm</span></div>
                                </div>
                            </div>
                            
                            <div class="health-grid" style="margin-top:16px">
                                <div class="health-item" style="grid-column: span 2">
                                    <div class="health-label">Nivel de Actividad</div>
                                    <div class="health-value" style="font-size:1.05rem"><?php echo $labelActivity[$lastPlan['actividad']] ?? $lastPlan['actividad']; ?></div>
                                </div>
                                <div class="health-item" style="grid-column: span 2">
                                    <div class="health-label">Tipo de Dieta</div>
                                    <div class="health-value" style="font-size:1.05rem"><?php echo $labelDiet[$lastPlan['diet_type']] ?? $lastPlan['diet_type']; ?></div>
                                </div>
                            </div>
                            
                            <!-- BMI Analyzer -->
                            <?php if ($bmi): ?>
                                <div class="bmi-section">
                                    <div class="bmi-header">
                                        <div class="bmi-score-box">
                                            <span class="bmi-num"><?php echo $bmi; ?></span>
                                            <span class="bmi-lbl">IMC (Índice de Masa Corporal)</span>
                                        </div>
                                        <span class="bmi-status-badge <?php 
                                            if ($bmi < 18.5) echo 'bmi-underweight';
                                            elseif ($bmi < 25) echo 'bmi-healthy';
                                            elseif ($bmi < 30) echo 'bmi-overweight';
                                            else echo 'bmi-obese';
                                        ?>">
                                            <?php echo $bmiStatus; ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Visual Progress Bar for BMI -->
                                    <div class="bmi-track">
                                        <div class="bmi-pointer" style="width: <?php echo $bmiPercent; ?>%"></div>
                                    </div>
                                    <div class="bmi-axis">
                                        <span>Bajo Peso (<18.5)</span>
                                        <span>Normal (18.5 - 24.9)</span>
                                        <span>Sobrepeso (25 - 29.9)</span>
                                        <span>Obesidad (>=30)</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Daily Targets summary -->
                        <div class="results-card animate-in" style="margin-top:0">
                            <h3 style="font-size:1.15rem;font-weight:700;margin-bottom:20px;text-align:center">
                                <i class="fas fa-bullseye" style="color:var(--accent)"></i> Tu Objetivo: <?php echo $labelObjective[$lastPlan['objetivo']] ?? $lastPlan['objetivo']; ?>
                            </h3>
                            
                            <div class="calorie-display">
                                <div class="number"><?php echo sanitize($lastPlan['target_calories']); ?></div>
                                <div class="unit">kcal diarias recomendadas</div>
                                <p style="font-size:0.85rem;color:var(--text-muted);margin-top:8px">
                                    Esta cantidad de calorías te guiará eficientemente hacia tu meta.
                                </p>
                            </div>
                            
                            <div class="macro-grid">
                                <div class="macro-item">
                                    <div class="macro-value"><?php echo sanitize($lastPlan['protein']); ?>g</div>
                                    <div class="macro-label">Proteínas (4 kcal/g)</div>
                                </div>
                                <div class="macro-item">
                                    <div class="macro-value"><?php echo sanitize($lastPlan['carbs']); ?>g</div>
                                    <div class="macro-label">Carbohidratos (4 kcal/g)</div>
                                </div>
                                <div class="macro-item">
                                    <div class="macro-value"><?php echo sanitize($lastPlan['fat']); ?>g</div>
                                    <div class="macro-label">Grasas (9 kcal/g)</div>
                                </div>
                                <div class="macro-item">
                                    <div class="macro-value"><?php echo sanitize($lastPlan['target_calories']); ?></div>
                                    <div class="macro-label">Kcal Objetivo</div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- User has no plans generated yet -->
                        <div class="empty-state animate-in">
                            <i class="fas fa-calculator"></i>
                            <h3>Aún no has generado ningún plan</h3>
                            <p>Utiliza nuestra calculadora nutricional avanzada e inteligente para calcular tus necesidades calóricas exactas y recibir un menú adaptado de inmediato.</p>
                            <a href="<?php echo baseUrl(); ?>/calculator/index.php" class="btn btn-primary">
                                <i class="fas fa-magic"></i> Calcular mi primer plan
                            </a>
                        </div>
                    <?php endif; ?>
                </section>
                
                <!-- Tab 2: User plans history -->
                <section id="panel-plans" class="dashboard-panel">
                    <div class="summary-header">
                        <h2 class="summary-title"><i class="fas fa-history" style="color:var(--accent)"></i> Historial de Planes Solicitados</h2>
                        <p class="summary-subtitle">Accede, imprime o elimina todos los planes alimentarios que has generado en la plataforma.</p>
                    </div>
                    
                    <?php if (empty($allPlans)): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>Tu historial está vacío</h3>
                            <p>¿Listo para organizar tu alimentación? Calcula un menú personalizado en pocos segundos con nuestra calculadora inteligente.</p>
                            <a href="<?php echo baseUrl(); ?>/calculator/index.php" class="btn btn-primary">
                                <i class="fas fa-calculator"></i> Generar plan nutricional
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="plans-list">
                            <?php foreach ($allPlans as $p): ?>
                                <div class="history-card animate-in" id="plan-card-<?php echo $p['id']; ?>">
                                    <div class="history-header">
                                        <div>
                                            <span class="history-date">
                                                <i class="far fa-calendar-alt"></i> 
                                                <?php echo date('d \d\e F, Y — H:i', strtotime($p['created_at'])); ?>
                                            </span>
                                            <div style="display:flex;gap:6px;margin-top:6px">
                                                <span class="tag" style="background:rgba(6, 182, 212, 0.08);color:var(--accent2);border-color:rgba(6, 182, 212, 0.1)">
                                                    <?php echo $p['plan_type'] === 'completo' ? 'Día Completo' : 'Receta Única'; ?>
                                                </span>
                                                <span class="tag">
                                                    <?php echo $labelDiet[$p['diet_type']] ?? $p['diet_type']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="history-cal-box">
                                            <div class="history-calories"><?php echo sanitize($p['target_calories']); ?> <small style="display:inline;font-size:0.8rem;color:var(--text-muted)">kcal</small></div>
                                            <div class="history-goal"><?php echo $labelObjective[$p['objetivo']] ?? $p['objetivo']; ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Plan biometrics group -->
                                    <div class="history-bio-summary">
                                        <span class="history-badge">Peso: <strong><?php echo sanitize($p['peso']); ?> kg</strong></span>
                                        <span class="history-badge">Altura: <strong><?php echo sanitize($p['altura']); ?> cm</strong></span>
                                        <span class="history-badge">Edad: <strong><?php echo sanitize($p['edad']); ?> años</strong></span>
                                        <span class="history-badge">Actividad: <strong><?php 
                                            $actParts = explode(' ', $labelActivity[$p['actividad']] ?? $p['actividad']);
                                            echo sanitize($actParts[0]); 
                                        ?></strong></span>
                                        <span class="history-badge" style="border-color:rgba(16, 185, 129, 0.2)">Macros: 
                                            <strong style="color:var(--accent-light)">P: <?php echo sanitize($p['protein']); ?>g</strong> | 
                                            <strong style="color:var(--accent2)">HC: <?php echo sanitize($p['carbs']); ?>g</strong> | 
                                            <strong style="color:var(--warning)">G: <?php echo sanitize($p['fat']); ?>g</strong>
                                        </span>
                                    </div>
                                    
                                    <!-- Meal options -->
                                    <h4 style="font-size:0.9rem;font-weight:700;color:var(--text-secondary);margin-bottom:10px">
                                        <i class="fas fa-utensils"></i> Platos Sugeridos
                                    </h4>
                                    
                                    <div class="plan-meals-grid">
                                        <?php if ($p['plan_type'] === 'completo'): ?>
                                            <!-- Breakfast -->
                                            <?php if ($p['breakfast_title']): ?>
                                                <a href="<?php echo baseUrl(); ?>/recipes/detail.php?id=<?php echo $p['breakfast_id']; ?>" class="plan-meal-item">
                                                    <div class="plan-meal-thumb">
                                                        <img src="<?php echo sanitize($p['breakfast_image']); ?>" alt="Desayuno">
                                                    </div>
                                                    <div class="plan-meal-details">
                                                        <div class="plan-meal-category">🌅 Desayuno</div>
                                                        <div class="plan-meal-title"><?php echo sanitize($p['breakfast_title']); ?></div>
                                                        <div class="plan-meal-calories"><?php echo $p['breakfast_calories']; ?> kcal</div>
                                                    </div>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Lunch -->
                                            <?php if ($p['lunch_title']): ?>
                                                <a href="<?php echo baseUrl(); ?>/recipes/detail.php?id=<?php echo $p['lunch_id']; ?>" class="plan-meal-item">
                                                    <div class="plan-meal-thumb">
                                                        <img src="<?php echo sanitize($p['lunch_image']); ?>" alt="Comida">
                                                    </div>
                                                    <div class="plan-meal-details">
                                                        <div class="plan-meal-category">🍽️ Comida</div>
                                                        <div class="plan-meal-title"><?php echo sanitize($p['lunch_title']); ?></div>
                                                        <div class="plan-meal-calories"><?php echo $p['lunch_calories']; ?> kcal</div>
                                                    </div>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Dinner -->
                                            <?php if ($p['dinner_title']): ?>
                                                <a href="<?php echo baseUrl(); ?>/recipes/detail.php?id=<?php echo $p['dinner_id']; ?>" class="plan-meal-item">
                                                    <div class="plan-meal-thumb">
                                                        <img src="<?php echo sanitize($p['dinner_image']); ?>" alt="Cena">
                                                    </div>
                                                    <div class="plan-meal-details">
                                                        <div class="plan-meal-category">🌙 Cena</div>
                                                        <div class="plan-meal-title"><?php echo sanitize($p['dinner_title']); ?></div>
                                                        <div class="plan-meal-calories"><?php echo $p['dinner_calories']; ?> kcal</div>
                                                    </div>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Snack -->
                                            <?php if ($p['snack_title']): ?>
                                                <a href="<?php echo baseUrl(); ?>/recipes/detail.php?id=<?php echo $p['snack_id']; ?>" class="plan-meal-item">
                                                    <div class="plan-meal-thumb">
                                                        <img src="<?php echo sanitize($p['snack_image']); ?>" alt="Snack">
                                                    </div>
                                                    <div class="plan-meal-details">
                                                        <div class="plan-meal-category">🍎 Snack</div>
                                                        <div class="plan-meal-title"><?php echo sanitize($p['snack_title']); ?></div>
                                                        <div class="plan-meal-calories"><?php echo $p['snack_calories']; ?> kcal</div>
                                                    </div>
                                                </a>
                                            <?php endif; ?>
                                            
                                        <?php else: ?>
                                            <!-- Single Recipe Plan -->
                                            <?php if ($p['breakfast_title']): ?>
                                                <a href="<?php echo baseUrl(); ?>/recipes/detail.php?id=<?php echo $p['breakfast_id']; ?>" class="plan-meal-item" style="grid-column: span 4">
                                                    <div class="plan-meal-thumb" style="width:70px;height:70px">
                                                        <img src="<?php echo sanitize($p['breakfast_image']); ?>" alt="Receta">
                                                    </div>
                                                    <div class="plan-meal-details">
                                                        <div class="plan-meal-category">🍽️ Receta Recomendada</div>
                                                        <div class="plan-meal-title" style="font-size:0.95rem"><?php echo sanitize($p['breakfast_title']); ?></div>
                                                        <div class="plan-meal-calories" style="font-size:0.85rem"><?php echo $p['breakfast_calories']; ?> kcal</div>
                                                    </div>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Plan Actions -->
                                    <div class="plan-actions no-print">
                                        <button onclick="printSpecificPlan(<?php echo $p['id']; ?>)" class="btn btn-sm btn-outline">
                                            <i class="fas fa-print"></i> Imprimir Plan
                                        </button>
                                        <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este plan de tu historial?');" style="display:inline">
                                            <input type="hidden" name="action" value="delete_plan">
                                            <input type="hidden" name="plan_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline btn-danger" style="border-color:var(--danger);color:var(--danger)">
                                                <i class="fas fa-trash-alt"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                
                <!-- Tab: Favorites -->
                <section id="panel-favorites" class="dashboard-panel">
                    <div class="summary-header">
                        <h2 class="summary-title"><i class="fas fa-heart" style="color:#f87171"></i> Mis Recetas Favoritas</h2>
                        <p class="summary-subtitle">Tus recetas guardadas para tenerlas siempre a mano en tu panel personal.</p>
                    </div>
                    
                    <?php if (empty($myFavorites)): ?>
                        <div class="empty-state">
                            <i class="fas fa-heart" style="color:var(--text-muted)"></i>
                            <h3>Aún no tienes recetas favoritas</h3>
                            <p>Explora nuestro catálogo de recetas saludables y marca con un corazón las que más te gusten.</p>
                            <a href="<?php echo baseUrl(); ?>/recipes/index.php" class="btn btn-primary">
                                <i class="fas fa-utensils"></i> Explorar Recetas
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="recipes-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:24px;">
                            <?php foreach ($myFavorites as $favRecipe): ?>
                                <div class="recipe-card" id="fav-card-<?php echo $favRecipe['id']; ?>" style="position:relative;">
                                    <div class="recipe-card-image" style="position:relative; height:200px; border-radius:var(--radius-sm); overflow:hidden;">
                                        <img src="<?php echo sanitize($favRecipe['image_url']); ?>" alt="<?php echo sanitize($favRecipe['title']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <span class="recipe-card-badge" style="position:absolute; top:12px; left:12px; background:rgba(11,15,25,0.7); backdrop-filter:blur(5px); padding:4px 10px; border-radius:30px; font-size:0.7rem; font-weight:700; color:var(--accent-light); border:1px solid rgba(255,255,255,0.08);"><?php echo sanitize($favRecipe['category']); ?></span>
                                        <button class="fav-toggle-btn active" data-id="<?php echo $favRecipe['id']; ?>" aria-label="Quitar de favoritos" style="position:absolute; top:12px; right:12px; width:36px; height:36px; border-radius:50%; background:rgba(11,15,25,0.6); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index:10; box-shadow:0 0 10px rgba(239,68,68,0.25);">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                    <a href="<?php echo baseUrl(); ?>/recipes/detail.php?id=<?php echo $favRecipe['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
                                        <div class="recipe-card-body" style="padding:16px 0 0 0;">
                                            <h3 style="font-size:1.05rem; font-weight:800; margin:0 0 6px 0;"><?php echo sanitize($favRecipe['title']); ?></h3>
                                            <p style="color:var(--text-secondary); font-size:0.82rem; margin:0 0 14px 0; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo sanitize($favRecipe['description']); ?></p>
                                            <div class="recipe-meta" style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-muted); border-top:1px solid var(--border-glass); padding-top:12px;">
                                                <span><i class="fas fa-clock"></i> <?php echo $favRecipe['prep_time'] + $favRecipe['cook_time']; ?> min</span>
                                                <span><i class="fas fa-fire"></i> <?php echo $favRecipe['calories']; ?> kcal</span>
                                                <span><i class="fas fa-signal"></i> <?php echo sanitize($favRecipe['difficulty']); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                
            <?php else: ?>
                <!-- ==================== ADMINISTRATOR PANELS ==================== -->
                
                <!-- Tab 1: Admin Dashboard stats -->
                <section id="panel-admin-dashboard" class="dashboard-panel active">
                    <div class="summary-header">
                        <h2 class="summary-title"><i class="fas fa-user-shield" style="color:var(--warning)"></i> Centro de Control NutriFit</h2>
                        <p class="summary-subtitle">Supervisión administrativa de contenidos, estadísticas globales y accesos de usuarios.</p>
                    </div>
                    
                    <!-- Admin key metrics -->
                    <div class="admin-stats-grid">
                        <div class="admin-stat-box animate-in">
                            <div class="admin-stat-icon green"><i class="fas fa-users"></i></div>
                            <div>
                                <div class="admin-stat-num"><?php echo $totalUsers; ?></div>
                                <div class="admin-stat-lbl">Usuarios Registrados</div>
                            </div>
                        </div>
                        <div class="admin-stat-box animate-in" style="animation-delay:0.1s">
                            <div class="admin-stat-icon blue"><i class="fas fa-utensils"></i></div>
                            <div>
                                <div class="admin-stat-num"><?php echo $totalRecipes; ?></div>
                                <div class="admin-stat-lbl">Recetas en Catálogo</div>
                            </div>
                        </div>
                        <div class="admin-stat-box animate-in" style="animation-delay:0.2s">
                            <div class="admin-stat-icon orange"><i class="fas fa-calculator"></i></div>
                            <div>
                                <div class="admin-stat-num"><?php echo $totalSavedPlans; ?></div>
                                <div class="admin-stat-lbl">Planes Generados</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Administrative shortcuts -->
                    <div class="health-profile-card animate-in">
                        <h3 style="font-size:1.15rem;font-weight:700;margin-bottom:16px"><i class="fas fa-star" style="color:var(--warning)"></i> Acceso Rápido a Tareas Administrativas</h3>
                        <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:24px">
                            Gestiona el contenido de NutriFit, añade nuevas recetas saludables o modera las cuentas directamente.
                        </p>
                        
                        <div class="features-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));gap:16px">
                            <a href="<?php echo baseUrl(); ?>/admin/recipe_form.php" class="btn btn-primary" style="justify-content:center">
                                <i class="fas fa-plus-circle"></i> Nueva Receta
                            </a>
                            <a href="<?php echo baseUrl(); ?>/admin/recipes.php" class="btn btn-outline" style="justify-content:center">
                                <i class="fas fa-utensils"></i> Gestionar Recetas
                            </a>
                            <a href="<?php echo baseUrl(); ?>/admin/users.php" class="btn btn-outline" style="justify-content:center;border-color:var(--accent2);color:var(--accent2)">
                                <i class="fas fa-users-cog"></i> Gestionar Usuarios
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick View: Recently Joined Users -->
                    <div class="health-profile-card animate-in" style="margin-top:24px">
                        <h3 style="font-size:1.15rem;font-weight:700;margin-bottom:16px"><i class="fas fa-user-plus" style="color:var(--accent2)"></i> Usuarios Registrados Recientemente</h3>
                        
                        <div class="table-responsive">
                            <table class="table-fancy">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Fecha Registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $ru): ?>
                                        <tr>
                                            <td><strong><?php echo sanitize($ru['username']); ?></strong></td>
                                            <td><?php echo sanitize($ru['email']); ?></td>
                                            <td>
                                                <span class="tag <?php echo $ru['role'] === 'admin' ? 'role-admin' : ''; ?>" style="font-size:0.7rem;text-transform:uppercase">
                                                    <?php echo $ru['role']; ?>
                                                </span>
                                            </td>
                                            <td style="color:var(--text-muted)"><?php echo date('d/m/Y H:i', strtotime($ru['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <!-- Tab 2: Admin Community Calculations (User Activity Log) -->
                <section id="panel-admin-community" class="dashboard-panel">
                    <div class="summary-header">
                        <h2 class="summary-title"><i class="fas fa-stethoscope" style="color:var(--accent)"></i> Actividad de Planes Nutricionales</h2>
                        <p class="summary-subtitle">Monitorea y analiza en tiempo real qué planes están generando tus usuarios y cuáles son sus objetivos principales.</p>
                    </div>
                    
                    <?php if (empty($recentPlansAdmin)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calculator"></i>
                            <h3>No hay actividad registrada aún</h3>
                            <p>Los planes generados por los usuarios registrados se listarán en tiempo real en este apartado.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-fancy">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                        <th>Biometría</th>
                                        <th>Objetivo</th>
                                        <th>Plan / Calorías</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPlansAdmin as $ra): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo sanitize($ra['username']); ?></strong>
                                                <div style="font-size:0.75rem;color:var(--text-muted)"><?php echo sanitize($ra['email']); ?></div>
                                            </td>
                                            <td style="font-size:0.8rem"><?php echo date('d/m/Y H:i', strtotime($ra['created_at'])); ?></td>
                                            <td style="font-size:0.82rem">
                                                <?php echo $ra['genero'] === 'male' ? 'H' : 'M'; ?>, 
                                                <strong><?php echo sanitize($ra['peso']); ?> kg</strong>, 
                                                <?php echo sanitize($ra['altura']); ?> cm, 
                                                <?php echo sanitize($ra['edad']); ?> a.
                                            </td>
                                            <td>
                                                <span class="tag" style="font-size:0.75rem">
                                                    <?php echo $ra['objetivo']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="color:var(--accent-light);font-weight:700"><?php echo sanitize($ra['target_calories']); ?> kcal</span>
                                                <div style="font-size:0.75rem;color:var(--text-muted)"><?php echo $ra['plan_type'] === 'completo' ? 'Día Completo' : 'Receta'; ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
                
                <!-- Tab 3: Admin Users Table -->
                <section id="panel-admin-users" class="dashboard-panel">
                    <div class="summary-header">
                        <h2 class="summary-title"><i class="fas fa-users-cog" style="color:var(--accent2)"></i> Base de Datos de Usuarios</h2>
                        <p class="summary-subtitle">Listado y gestión completa de todas las cuentas creadas en el sistema.</p>
                    </div>
                    
                    <div class="table-responsive animate-in">
                        <table class="table-fancy">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Unido el</th>
                                    <th style="text-align:right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $uData): ?>
                                    <tr>
                                        <td>#<?php echo $uData['id']; ?></td>
                                        <td><strong><?php echo sanitize($uData['username']); ?></strong></td>
                                        <td><?php echo sanitize($uData['email']); ?></td>
                                        <td>
                                            <span class="profile-role-badge <?php echo $uData['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>" style="padding: 2px 10px; font-size:0.68rem; margin-bottom:0">
                                                <?php echo $uData['role']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($uData['created_at'])); ?></td>
                                        <td style="text-align:right">
                                            <?php if ($uData['id'] != $userId): ?>
                                                <a href="<?php echo baseUrl(); ?>/admin/delete_user.php?id=<?php echo $uData['id']; ?>" class="btn btn-sm btn-outline" style="border-color:var(--danger);color:var(--danger);padding:4px 10px" onclick="return confirm('¿De verdad quieres eliminar permanentemente a este usuario?');">
                                                    <i class="fas fa-user-times"></i> Eliminar
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size:0.8rem;color:var(--text-muted);font-style:italic">Tú (Admin)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-right" style="margin-top:16px">
                        <a href="<?php echo baseUrl(); ?>/admin/users.php" class="btn btn-outline">
                            <i class="fas fa-external-link-alt"></i> Ver Administrador de Usuarios completo
                        </a>
                    </div>
                </section>
                
            <?php endif; ?>
            
            <!-- Tab: Settings (Common for both users and admins) -->
            <section id="panel-settings" class="dashboard-panel">
                <div class="summary-header">
                    <h2 class="summary-title"><i class="fas fa-user-cog" style="color:var(--accent2)"></i> Configuración de la Cuenta</h2>
                    <p class="summary-subtitle">Actualiza tu dirección de correo electrónico y gestiona la seguridad de tu contraseña.</p>
                </div>
                
                <div class="form-panel animate-in">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user" style="margin-right:6px"></i> Nombre de Usuario</label>
                            <input type="text" id="username" value="<?php echo sanitize($user['username']); ?>" disabled style="opacity:0.6;cursor:not-allowed">
                            <small style="color:var(--text-muted);display:block;margin-top:4px">Por motivos de seguridad, el nombre de usuario no puede ser modificado.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope" style="margin-right:6px"></i> Correo Electrónico</label>
                            <input type="email" id="email" name="email" value="<?php echo sanitize($user['email']); ?>" required placeholder="Introduce tu email">
                        </div>
                        
                        <div class="form-separator"></div>
                        
                        <h4 style="font-size:1rem;font-weight:700;margin-bottom:12px;color:var(--accent-light)"><i class="fas fa-key"></i> Cambiar Contraseña</h4>
                        <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:20px">Completa los campos a continuación solo si deseas renovar tu contraseña de acceso.</p>
                        
                        <div class="form-group">
                            <label for="current_password">Contraseña Actual</label>
                            <input type="password" id="current_password" name="current_password" placeholder="••••••••">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Mínimo 6 caracteres">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Nueva Contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top:10px">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </section>
            
        </main>
        
    </div>
</div>

<script>
    // Tab switching controller
    function switchTab(tabId) {
        // Deactivate all menu links
        const links = document.querySelectorAll('.profile-menu-link');
        links.forEach(link => link.classList.remove('active'));
        
        // Deactivate all panels
        const panels = document.querySelectorAll('.dashboard-panel');
        panels.forEach(panel => panel.classList.remove('active'));
        
        // Find the matching button and activate it
        const targetBtn = Array.from(links).find(link => link.getAttribute('onclick').includes(`'${tabId}'`));
        if (targetBtn) {
            targetBtn.classList.add('active');
        }
        
        // Activate panel
        const activePanel = document.getElementById(`panel-${tabId}`);
        if (activePanel) {
            activePanel.classList.add('active');
            // Store selected tab in localStorage for better UX
            localStorage.setItem('activeProfileTab', tabId);
        }
    }
    
    // Print a specific plan container
    function printSpecificPlan(planId) {
        // We will copy style sheets and card content to a print window
        const card = document.getElementById(`plan-card-${planId}`);
        if (!card) return;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Plan Nutricional — NutriFit</title>');
        // Load styles
        printWindow.document.write('<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">');
        printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">');
        printWindow.document.write('<style>');
        printWindow.document.write(`
            body { font-family: 'Inter', sans-serif; background: #fff !important; color: #000 !important; padding: 20px; }
            .history-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
            .history-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #10b981; padding-bottom: 12px; margin-bottom: 20px; }
            .history-date { font-size: 0.85rem; color: #64748b; }
            .history-cal-box { text-align: right; }
            .history-calories { font-size: 1.6rem; font-weight: 800; color: #10b981; }
            .history-goal { font-size: 0.8rem; text-transform: uppercase; color: #475569; font-weight: 700; }
            .history-bio-summary { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
            .history-badge { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; color: #334155; }
            .plan-meals-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 15px; }
            .plan-meal-item { display: flex; align-items: center; gap: 12px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-decoration: none; color: inherit; }
            .plan-meal-thumb { width: 60px; height: 60px; border-radius: 6px; overflow: hidden; }
            .plan-meal-thumb img { width: 100%; height: 100%; object-fit: cover; }
            .plan-meal-category { font-size: 0.72rem; color: #10b981; text-transform: uppercase; font-weight: 800; }
            .plan-meal-title { font-size: 0.9rem; font-weight: 700; margin: 2px 0; }
            .plan-meal-calories { font-size: 0.78rem; color: #64748b; }
            .tag { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; background: #e0f2fe; color: #0369a1; margin-right: 4px; font-weight:600; }
            .no-print { display: none !important; }
        `);
        printWindow.document.write('</style></head><body>');
        printWindow.document.write('<div style="text-align:center;margin-bottom:20px"><h1 style="color:#10b981;margin-bottom:5px"><i class="fas fa-leaf"></i> NutriFit</h1><p style="color:#64748b;font-size:0.9rem">Tu Plan Alimentario Personalizado Inteligente</p></div>');
        printWindow.document.write(card.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        // Wait for assets to load, then print
        printWindow.onload = function() {
            printWindow.print();
            // Close after printing
            setTimeout(() => printWindow.close(), 500);
        };
    }
    
    // Auto-restore tab state from localStorage
    document.addEventListener('DOMContentLoaded', () => {
        const savedTab = localStorage.getItem('activeProfileTab');
        if (savedTab) {
            // Validate if the tab is valid for the current role
            const panel = document.getElementById(`panel-${savedTab}`);
            if (panel) {
                switchTab(savedTab);
            }
        }
    });

    // AJAX heart toggling in favorites tab
    document.querySelectorAll('#panel-favorites .fav-toggle-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const recipeId = btn.dataset.id;
            const fd = new FormData();
            fd.append('recipe_id', recipeId);
            
            btn.disabled = true;
            btn.style.opacity = '0.5';
            
            try {
                const res = await fetch('<?php echo baseUrl(); ?>/api/favorite.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success && !data.favorited) {
                    const card = document.getElementById('fav-card-' + recipeId);
                    if (card) {
                        card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8) translateY(15px)';
                        setTimeout(() => {
                            card.remove();
                            // Reload if grid becomes empty to display default illustration
                            const grid = document.querySelector('#panel-favorites .recipes-grid');
                            if (grid && grid.children.length === 0) {
                                location.reload();
                            }
                        }, 400);
                    }
                }
            } catch (err) {
                console.error(err);
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
