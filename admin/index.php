<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAdmin();

$db = getDB();

$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRecipes = $db->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
$totalVegan = $db->query("SELECT COUNT(*) FROM recipes WHERE diet_type = 'vegano'")->fetchColumn();

$pageTitle = 'Panel de Administración';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-page">
    <div class="container">
        <div class="admin-header">
            <h1><i class="fas fa-cogs" style="color:var(--accent)"></i> Panel de Administración</h1>
        </div>

        <!-- Stats -->
        <div class="stat-cards">
            <div class="stat-card animate-in">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-label">Usuarios registrados</div>
                </div>
            </div>
            <div class="stat-card animate-in">
                <div class="stat-icon blue"><i class="fas fa-utensils"></i></div>
                <div>
                    <div class="stat-value"><?= $totalRecipes ?></div>
                    <div class="stat-label">Recetas totales</div>
                </div>
            </div>
            <div class="stat-card animate-in">
                <div class="stat-icon orange"><i class="fas fa-leaf"></i></div>
                <div>
                    <div class="stat-value"><?= $totalVegan ?></div>
                    <div class="stat-label">Recetas veganas</div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="features-grid" style="grid-template-columns: 1fr 1fr;">
            <a href="recipes.php" class="feature-card" style="text-decoration:none;color:inherit;text-align:left">
                <div class="feature-icon"><i class="fas fa-utensils"></i></div>
                <h3>Gestionar Recetas</h3>
                <p>Crear, editar y eliminar recetas de la base de datos.</p>
            </a>
            <a href="users.php" class="feature-card" style="text-decoration:none;color:inherit;text-align:left">
                <div class="feature-icon"><i class="fas fa-users-cog"></i></div>
                <h3>Gestionar Usuarios</h3>
                <p>Ver y eliminar cuentas de usuario.</p>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
