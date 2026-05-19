<?php
require_once __DIR__ . '/../includes/functions.php';

// Get filters from URL
$filters = [];
if (!empty($_GET['category'])) $filters['category'] = $_GET['category'];
if (!empty($_GET['diet_type'])) $filters['diet_type'] = $_GET['diet_type'];
if (!empty($_GET['difficulty'])) $filters['difficulty'] = $_GET['difficulty'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

$recipes = getRecipesByFilters($filters);

$myFavIds = [];
if (isLoggedIn()) {
    $db = getDB();
    $stmtFavs = $db->prepare("SELECT recipe_id FROM favorites WHERE user_id = :uid");
    $stmtFavs->execute([':uid' => $_SESSION['user_id']]);
    $myFavIds = $stmtFavs->fetchAll(PDO::FETCH_COLUMN);
}

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
                            <div class="recipe-card" style="position:relative;">
                                <div class="recipe-card-image" style="position:relative; overflow:hidden;">
                                    <img src="<?= sanitize($recipe['image_url']) ?>" alt="<?= sanitize($recipe['title']) ?>" loading="lazy">
                                    <span class="recipe-card-badge"><?= sanitize($recipe['category']) ?></span>
                                    <?php if (isLoggedIn()):
                                        $isFav = in_array($recipe['id'], $myFavIds);
                                    ?>
                                        <button class="fav-toggle-btn <?= $isFav ? 'active' : '' ?>" data-id="<?= $recipe['id'] ?>" aria-label="Guardar receta" style="position:absolute; top:12px; right:12px; width:36px; height:36px; border-radius:50%; background:<?= $isFav ? 'rgba(239, 68, 68, 0.15)' : 'rgba(11, 15, 25, 0.6)' ?>; backdrop-filter:blur(8px); border:1px solid <?= $isFav ? 'rgba(239, 68, 68, 0.3)' : 'rgba(255, 255, 255, 0.1)' ?>; color:<?= $isFav ? '#ef4444' : '#fff' ?>; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index:10; outline:none; box-shadow:<?= $isFav ? '0 0 10px rgba(239, 68, 68, 0.25)' : 'none' ?>;">
                                            <i class="<?= $isFav ? 'fas' : 'far' ?> fa-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <a href="detail.php?id=<?= $recipe['id'] ?>" style="text-decoration:none; color:inherit; display:block;">
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.fav-toggle-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        const recipeId = btn.dataset.id;
        const fd = new FormData();
        fd.append('recipe_id', recipeId);
        
        btn.disabled = true;
        btn.style.transform = 'scale(0.8)';
        
        try {
            const res = await fetch('<?= baseUrl() ?>/api/favorite.php', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (data.success) {
                if (data.favorited) {
                    btn.classList.add('active');
                    btn.style.color = '#ef4444';
                    btn.style.background = 'rgba(239, 68, 68, 0.15)';
                    btn.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                    btn.style.boxShadow = '0 0 10px rgba(239, 68, 68, 0.25)';
                    btn.innerHTML = '<i class="fas fa-heart"></i>';
                } else {
                    btn.classList.remove('active');
                    btn.style.color = '#fff';
                    btn.style.background = 'rgba(11, 15, 25, 0.6)';
                    btn.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    btn.style.boxShadow = 'none';
                    btn.innerHTML = '<i class="far fa-heart"></i>';
                }
                btn.style.transform = 'scale(1.25)';
                setTimeout(() => {
                    btn.style.transform = 'scale(1)';
                    btn.disabled = false;
                }, 200);
            } else {
                btn.disabled = false;
                btn.style.transform = 'scale(1)';
            }
        } catch (err) {
            console.error(err);
            btn.disabled = false;
            btn.style.transform = 'scale(1)';
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
