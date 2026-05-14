<?php
require_once __DIR__ . '/includes/functions.php';

// Get featured recipes
$featuredRecipes = getRecipesByFilters(['limit' => 6]);

$pageTitle = 'Inicio';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1 class="animate-in">Cocina inteligente,<br>vida <span class="accent">saludable</span></h1>
        <p class="animate-in">Descubre recetas personalizadas, calcula tus necesidades nutricionales y genera planes alimentarios adaptados a tus objetivos. Todo en un solo lugar.</p>
        <div class="hero-buttons animate-in">
            <a href="calculator/index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-calculator"></i> Calcula tu plan
            </a>
            <a href="recipes/index.php" class="btn btn-outline btn-lg">
                <i class="fas fa-utensils"></i> Explorar recetas
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <div class="section-title">
            <h2>¿Cómo funciona?</h2>
            <p>Tres pasos para transformar tu alimentación</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-heartbeat"></i></div>
                <h3>Calcula tu metabolismo</h3>
                <p>Introduce tus datos básicos y descubre cuántas calorías necesitas al día según tu nivel de actividad.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-bullseye"></i></div>
                <h3>Elige tu objetivo</h3>
                <p>¿Quieres definir, mantener o ganar masa? Ajustamos las calorías y macros a tu meta personal.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-clipboard-list"></i></div>
                <h3>Genera tu plan</h3>
                <p>Recibe una receta o un plan completo (desayuno, comida, cena y snack) adaptado a tus preferencias y alergias.</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Recipes Section -->
<section class="recipes-section">
    <div class="container">
        <div class="section-title">
            <h2>Recetas destacadas</h2>
            <p>Descubre nuestra selección de recetas saludables y deliciosas</p>
        </div>
        <div class="recipes-grid">
            <?php foreach ($featuredRecipes as $recipe): ?>
                <?php $tags = getRecipeTags($recipe['id']); ?>
                <a href="recipes/detail.php?id=<?= $recipe['id'] ?>" class="recipe-card">
                    <div class="recipe-card-image">
                        <img src="<?= sanitize($recipe['image_url']) ?>" alt="<?= sanitize($recipe['title']) ?>" loading="lazy">
                        <span class="recipe-card-badge"><?= sanitize($recipe['category']) ?></span>
                    </div>
                    <div class="recipe-card-body">
                        <h3><?= sanitize($recipe['title']) ?></h3>
                        <p><?= sanitize($recipe['description']) ?></p>
                        <div class="recipe-meta">
                            <span><i class="fas fa-clock"></i> <?= $recipe['prep_time'] + $recipe['cook_time'] ?> min</span>
                            <span><i class="fas fa-fire"></i> <?= $recipe['calories'] ?> kcal</span>
                            <span><i class="fas fa-signal"></i> <?= sanitize($recipe['difficulty']) ?></span>
                        </div>
                        <?php if (!empty($tags)): ?>
                            <div class="recipe-tags">
                                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                    <span class="tag"><?= sanitize($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <a href="recipes/index.php" class="btn btn-outline btn-lg">
                <i class="fas fa-arrow-right"></i> Ver todas las recetas
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
