<?php
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$recipe = getRecipeById($id);
if (!$recipe) { header("Location: index.php"); exit; }

$ingredients = getRecipeIngredients($id);
$steps = getRecipeSteps($id);
$tags = getRecipeTags($id);

$pageTitle = $recipe['title'];
include __DIR__ . '/../includes/header.php';

// Calculate nutrition bar percentages (based on a 2000 kcal reference)
$calPct = min(100, round(($recipe['calories'] / 2000) * 100));
$protPct = min(100, round(($recipe['protein'] / 50) * 100));
$carbPct = min(100, round(($recipe['carbs'] / 300) * 100));
$fatPct = min(100, round(($recipe['fat'] / 65) * 100));
?>

<div class="recipe-detail">
    <div class="container">
        <!-- Header Image -->
        <div class="recipe-header-image animate-in">
            <img src="<?= sanitize($recipe['image_url']) ?>" alt="<?= sanitize($recipe['title']) ?>">
            <div class="overlay">
                <div class="recipe-tags" style="margin-bottom:10px">
                    <span class="tag"><?= sanitize($recipe['category']) ?></span>
                    <span class="tag"><?= sanitize($recipe['diet_type']) ?></span>
                    <?php foreach ($tags as $tag): ?>
                        <span class="tag"><?= sanitize($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <h1><?= sanitize($recipe['title']) ?></h1>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="recipe-meta" style="font-size:0.95rem;gap:24px;margin-bottom:8px">
            <span><i class="fas fa-clock"></i> Prep: <?= $recipe['prep_time'] ?> min</span>
            <span><i class="fas fa-fire-alt"></i> Cocción: <?= $recipe['cook_time'] ?> min</span>
            <span><i class="fas fa-users"></i> <?= $recipe['servings'] ?> raciones</span>
            <span><i class="fas fa-signal"></i> <?= sanitize($recipe['difficulty']) ?></span>
        </div>
        <p style="color:var(--text-secondary);font-size:0.95rem;line-height:1.7;margin-bottom:16px">
            <?= sanitize($recipe['description']) ?>
        </p>

        <!-- Two-column layout -->
        <div class="recipe-info-grid">
            <div>
                <!-- Ingredients -->
                <div class="ingredients-list animate-in">
                    <h3><i class="fas fa-carrot" style="color:var(--accent)"></i> Ingredientes</h3>
                    <?php foreach ($ingredients as $ing): ?>
                        <div class="ingredient-item">
                            <span class="qty"><?= sanitize($ing['quantity']) ?> <?= sanitize($ing['unit']) ?></span>
                            <span><?= sanitize($ing['ingredient_name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Steps -->
                <div class="steps-list animate-in">
                    <h3><i class="fas fa-list-ol" style="color:var(--accent)"></i> Preparación</h3>
                    <?php foreach ($steps as $step): ?>
                        <div class="step-item">
                            <div class="step-number"><?= $step['step_number'] ?></div>
                            <p><?= sanitize($step['instruction']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Nutrition Sidebar -->
            <div>
                <div class="nutrition-card animate-in">
                    <h3><i class="fas fa-chart-pie" style="color:var(--accent)"></i> Información Nutricional</h3>
                    <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:16px">Por ración</p>

                    <div class="nutrition-item">
                        <span class="label">Calorías</span>
                        <span class="value" style="color:var(--accent-light)"><?= $recipe['calories'] ?> kcal</span>
                    </div>
                    <div class="nutrition-bar"><div class="nutrition-bar-fill" style="width:<?= $calPct ?>%"></div></div>

                    <div class="nutrition-item" style="margin-top:16px">
                        <span class="label">Proteínas</span>
                        <span class="value"><?= $recipe['protein'] ?>g</span>
                    </div>
                    <div class="nutrition-bar"><div class="nutrition-bar-fill" style="width:<?= $protPct ?>%;background:linear-gradient(90deg,#3b82f6,#60a5fa)"></div></div>

                    <div class="nutrition-item" style="margin-top:16px">
                        <span class="label">Carbohidratos</span>
                        <span class="value"><?= $recipe['carbs'] ?>g</span>
                    </div>
                    <div class="nutrition-bar"><div class="nutrition-bar-fill" style="width:<?= $carbPct ?>%;background:linear-gradient(90deg,#f59e0b,#fbbf24)"></div></div>

                    <div class="nutrition-item" style="margin-top:16px">
                        <span class="label">Grasas</span>
                        <span class="value"><?= $recipe['fat'] ?>g</span>
                    </div>
                    <div class="nutrition-bar"><div class="nutrition-bar-fill" style="width:<?= $fatPct ?>%;background:linear-gradient(90deg,#ef4444,#f87171)"></div></div>

                    <div class="nutrition-item" style="margin-top:16px">
                        <span class="label">Fibra</span>
                        <span class="value"><?= $recipe['fiber'] ?>g</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3 no-print">
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Volver a recetas
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
