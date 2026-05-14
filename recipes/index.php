<?php
require_once __DIR__ . '/../includes/functions.php';

// Get filters from URL
$filters = [];
if (!empty($_GET['category'])) $filters['category'] = $_GET['category'];
if (!empty($_GET['diet_type'])) $filters['diet_type'] = $_GET['diet_type'];
if (!empty($_GET['difficulty'])) $filters['difficulty'] = $_GET['difficulty'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

$recipes = getRecipesByFilters($filters);

$pageTitle = 'Recetas';
include __DIR__ . '/../includes/header.php';
?>

<div class="catalog-page">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-utensils" style="color:var(--accent)"></i> Nuestras Recetas</h1>
            <p>Explora nuestra colección de recetas saludables y deliciosas</p>
        </div>

        <div class="catalog-layout">
            <!-- Filter Sidebar -->
            <aside class="filter-sidebar no-print">
                <h3><i class="fas fa-filter"></i> Filtros</h3>
                <form method="GET" action="">
                    <div class="filter-group">
                        <label>Buscar</label>
                        <input type="text" name="search" value="<?= sanitize($_GET['search'] ?? '') ?>" placeholder="Buscar receta...">
                    </div>
                    <div class="filter-group">
                        <label>Categoría</label>
                        <select name="category">
                            <option value="">Todas</option>
                            <option value="desayuno" <?= ($_GET['category'] ?? '') === 'desayuno' ? 'selected' : '' ?>>🌅 Desayuno</option>
                            <option value="comida" <?= ($_GET['category'] ?? '') === 'comida' ? 'selected' : '' ?>>🍽️ Comida</option>
                            <option value="cena" <?= ($_GET['category'] ?? '') === 'cena' ? 'selected' : '' ?>>🌙 Cena</option>
                            <option value="snack" <?= ($_GET['category'] ?? '') === 'snack' ? 'selected' : '' ?>>🍎 Snack</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tipo de dieta</label>
                        <select name="diet_type">
                            <option value="">Todas</option>
                            <option value="omnivoro" <?= ($_GET['diet_type'] ?? '') === 'omnivoro' ? 'selected' : '' ?>>🥩 Omnívoro</option>
                            <option value="vegetariano" <?= ($_GET['diet_type'] ?? '') === 'vegetariano' ? 'selected' : '' ?>>🥚 Vegetariano</option>
                            <option value="vegano" <?= ($_GET['diet_type'] ?? '') === 'vegano' ? 'selected' : '' ?>>🌱 Vegano</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Dificultad</label>
                        <select name="difficulty">
                            <option value="">Todas</option>
                            <option value="facil" <?= ($_GET['difficulty'] ?? '') === 'facil' ? 'selected' : '' ?>>Fácil</option>
                            <option value="media" <?= ($_GET['difficulty'] ?? '') === 'media' ? 'selected' : '' ?>>Media</option>
                            <option value="dificil" <?= ($_GET['difficulty'] ?? '') === 'dificil' ? 'selected' : '' ?>>Difícil</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="index.php" class="btn btn-outline btn-block mt-2" style="text-align:center">Limpiar filtros</a>
                </form>
            </aside>

            <!-- Recipe Grid -->
            <div>
                <p style="color:var(--text-muted);margin-bottom:20px;font-size:0.9rem;">
                    <strong><?= count($recipes) ?></strong> recetas encontradas
                </p>

                <?php if (empty($recipes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No se encontraron recetas</h3>
                        <p>Prueba con otros filtros o términos de búsqueda.</p>
                    </div>
                <?php else: ?>
                    <div class="recipes-grid">
                        <?php foreach ($recipes as $recipe): ?>
                            <?php $tags = getRecipeTags($recipe['id']); ?>
                            <a href="detail.php?id=<?= $recipe['id'] ?>" class="recipe-card">
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
