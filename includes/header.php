<?php $flash = getFlash(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — NutriFit' : 'NutriFit — Recetas Inteligentes' ?></title>
    <meta name="description" content="Plataforma inteligente de recetas y planificación alimentaria personalizada.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= baseUrl() ?>/css/style.css">
</head>
<body>
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="<?= baseUrl() ?>/index.php" class="nav-logo">
                <i class="fas fa-leaf"></i> Nutri<span>Fit</span>
            </a>
            <button class="nav-toggle" id="navToggle" aria-label="Menú">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="<?= baseUrl() ?>/index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="<?= baseUrl() ?>/recipes/index.php"><i class="fas fa-utensils"></i> Recetas</a></li>
                <li><a href="<?= baseUrl() ?>/calculator/index.php"><i class="fas fa-calculator"></i> Calculadora</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= baseUrl() ?>/admin/index.php"><i class="fas fa-cogs"></i> Admin</a></li>
                <?php endif; ?>
                <li class="nav-auth">
                    <?php if (isLoggedIn()): ?>
                        <span class="nav-user"><i class="fas fa-user-circle"></i> <?= sanitize($_SESSION['username']) ?></span>
                        <a href="<?= baseUrl() ?>/auth/logout.php" class="btn btn-sm btn-outline">Cerrar sesión</a>
                    <?php else: ?>
                        <a href="<?= baseUrl() ?>/auth/login.php" class="btn btn-sm btn-outline">Entrar</a>
                        <a href="<?= baseUrl() ?>/auth/register.php" class="btn btn-sm btn-primary">Registro</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <?php if ($flash): ?>
        <div class="flash flash-<?= $flash['type'] ?>">
            <div class="container">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= sanitize($flash['message']) ?>
            </div>
        </div>
    <?php endif; ?>

    <main class="main-content">
