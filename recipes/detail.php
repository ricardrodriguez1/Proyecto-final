<?php
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$recipe = getRecipeById($id);
if (!$recipe) { header("Location: index.php"); exit; }

$ingredients = getRecipeIngredients($id);
$steps = getRecipeSteps($id);
$tags = getRecipeTags($id);

$db = getDB();

// Query general average rating & rating count
$stmtAvg = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as rating_count FROM recipe_ratings WHERE recipe_id = :rid");
$stmtAvg->execute([':rid' => $id]);
$ratingStats = $stmtAvg->fetch();
$avgRating = $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 1) : null;
$totalRatings = $ratingStats['rating_count'];

// Query comments feed
$stmtComments = $db->prepare("SELECT rc.*, u.username FROM recipe_comments rc JOIN users u ON rc.user_id = u.id WHERE rc.recipe_id = :rid ORDER BY rc.created_at DESC");
$stmtComments->execute([':rid' => $id]);
$comments = $stmtComments->fetchAll();

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

        <!-- Action Row (Favorites & Ratings) -->
        <div class="recipe-action-row animate-in" style="display:flex; align-items:center; gap:20px; flex-wrap:wrap; margin-bottom:30px; background:var(--bg-card); border:1px solid var(--border-glass); padding:20px 24px; border-radius:var(--radius); backdrop-filter:blur(10px);">
            <?php if (isLoggedIn()):
                // Check if favorited
                $stmtCheckFav = $db->prepare("SELECT id FROM favorites WHERE user_id = :uid AND recipe_id = :rid");
                $stmtCheckFav->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
                $isFav = (bool)$stmtCheckFav->fetch();
                
                // Get user rating if exists
                $stmtUserRating = $db->prepare("SELECT rating FROM recipe_ratings WHERE user_id = :uid AND recipe_id = :rid");
                $stmtUserRating->execute([':uid' => $_SESSION['user_id'], ':rid' => $id]);
                $userRating = $stmtUserRating->fetchColumn() ?: 0;
            ?>
                <!-- Favorite Toggle -->
                <button class="btn btn-fav-detail <?= $isFav ? 'active' : '' ?>" id="fav-detail-btn" data-id="<?= $id ?>" style="display:inline-flex; align-items:center; gap:10px; height:45px; border-radius:30px; border:1px solid <?= $isFav ? 'rgba(239, 68, 68, 0.3)' : 'rgba(255,255,255,0.08)' ?>; background:<?= $isFav ? 'rgba(239, 68, 68, 0.1)' : 'rgba(255,255,255,0.03)' ?>; color:<?= $isFav ? '#ef4444' : 'var(--text-primary)' ?>; font-weight:700; cursor:pointer; padding:0 24px; transition:all 0.3s ease; outline:none; box-shadow:<?= $isFav ? '0 0 12px rgba(239, 68, 68, 0.15)' : 'none' ?>;">
                    <i class="<?= $isFav ? 'fas' : 'far' ?> fa-heart" style="font-size:1.15rem;"></i>
                    <span id="fav-detail-text"><?= $isFav ? 'Guardado en Favoritos' : 'Añadir a Favoritos' ?></span>
                </button>
                
                <!-- Interactive Rating Star Widget -->
                <div class="rating-widget" style="display:flex; align-items:center; gap:12px;">
                    <span style="font-size:0.9rem; font-weight:600; color:var(--text-secondary);">Tu valoración:</span>
                    <div class="stars-container" style="display:flex; gap:6px;">
                        <?php for ($starIdx = 1; $starIdx <= 5; $starIdx++): ?>
                            <i class="fa-star <?= $starIdx <= $userRating ? 'fas' : 'far' ?>" data-val="<?= $starIdx ?>" style="cursor:pointer; font-size:1.4rem; color:<?= $starIdx <= $userRating ? 'var(--warning)' : 'var(--text-muted)' ?>; transition:all 0.2s ease;"></i>
                        <?php endfor; ?>
                    </div>
                    <span id="rating-status-lbl" style="font-size:0.8rem; font-weight:700; color:var(--accent-light);"></span>
                </div>
            <?php else: ?>
                <span style="font-size:0.9rem; color:var(--text-muted);">
                    <i class="fas fa-info-circle" style="color:var(--accent)"></i> <a href="<?= baseUrl() ?>/auth/login.php" style="color:var(--accent-light); font-weight:750; text-decoration:none;">Inicia sesión</a> para guardar en favoritos, valorar y comentar esta receta.
                </span>
            <?php endif; ?>
        </div>

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

        <!-- Feedback section -->
        <section class="feedback-section animate-in" style="margin-top:40px; border-top:1px solid var(--border-glass); padding-top:40px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:30px;">
                <h3 style="font-size:1.4rem; font-weight:800; margin:0;"><i class="fas fa-comments" style="color:var(--accent)"></i> Opiniones de la Comunidad</h3>
                <?php if ($avgRating): ?>
                    <div style="display:flex; align-items:center; gap:8px; font-weight:800; font-size:1.15rem; background:rgba(245,158,11,0.1); color:var(--warning); padding:6px 14px; border-radius:30px; border:1px solid rgba(245,158,11,0.15)">
                        <span><?= $avgRating ?> ⭐</span>
                        <span style="font-size:0.8rem; color:var(--text-muted); font-weight:600;">(<?= $totalRatings ?> valoraciones)</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="feedback-layout" style="display:grid; grid-template-columns:<?= isLoggedIn() ? '400px 1fr' : '1fr' ?>; gap:40px;">
                <?php if (isLoggedIn()): ?>
                    <!-- Comment box -->
                    <div class="comment-box" style="background:var(--bg-card); border:1px solid var(--border-glass); border-radius:var(--radius); padding:28px; height:fit-content;">
                        <h4 style="font-size:1.05rem; font-weight:700; margin-bottom:16px;">Deja tu comentario</h4>
                        <form id="comment-form">
                            <input type="hidden" name="recipe_id" value="<?= $id ?>">
                            <div class="form-group" style="margin-bottom:16px;">
                                <textarea name="comment" id="comment-text" rows="4" required placeholder="Comparte qué te ha parecido la receta..." style="width:100%; border:1px solid var(--border-glass); background:rgba(255,255,255,0.02); border-radius:var(--radius-sm); padding:12px; color:var(--text-primary); font-family:inherit; font-size:0.9rem; resize:none; outline:none; transition:all 0.3s ease;"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block" style="justify-content:center; gap:8px;">
                                <i class="fas fa-paper-plane"></i> Enviar Comentario
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- Comments list -->
                <div class="comments-list-wrapper">
                    <h4 style="font-size:1.05rem; font-weight:700; margin-bottom:20px;">Comentarios (<?= count($comments) ?>)</h4>
                    <div id="comments-container" style="display:flex; flex-direction:column; gap:16px;">
                        <?php if (empty($comments)): ?>
                            <p id="no-comments-msg" style="color:var(--text-muted); font-size:0.9rem; font-style:italic;">Nadie ha comentado todavía. ¡Sé el primero en compartir tu opinión!</p>
                        <?php else: foreach ($comments as $cm): ?>
                            <div class="comment-item" style="background:var(--bg-card); border:1px solid var(--border-glass); border-radius:var(--radius-sm); padding:18px; display:flex; gap:16px;">
                                <div class="comment-avatar" style="width:38px; height:38px; border-radius:50%; background:var(--gradient); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:0.9rem; flex-shrink:0;">
                                    <?= strtoupper(substr($cm['username'], 0, 1)) ?>
                                </div>
                                <div style="flex:1">
                                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:6px;">
                                        <strong style="font-size:0.9rem; color:var(--text-primary);"><?= sanitize($cm['username']) ?></strong>
                                        <small style="font-size:0.75rem; color:var(--text-muted);"><?= date('d/m/Y H:i', strtotime($cm['created_at'])) ?></small>
                                    </div>
                                    <p style="font-size:0.88rem; color:var(--text-secondary); line-height:1.6; margin:0;"><?= sanitize($cm['comment']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <div class="text-center mt-3 no-print" style="margin-top:40px">
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Volver a recetas
            </a>
        </div>
    </div>
</div>

<script>
const RECIPE_ID = <?= $id ?>;
const BASE_URL = '<?= baseUrl() ?>';

// Favorite button detail controller
const favBtn = document.getElementById('fav-detail-btn');
if (favBtn) {
    favBtn.addEventListener('click', async () => {
        favBtn.disabled = true;
        const fd = new FormData();
        fd.append('recipe_id', RECIPE_ID);
        try {
            const res = await fetch(BASE_URL + '/api/favorite.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                const heart = favBtn.querySelector('i');
                const txt = document.getElementById('fav-detail-text');
                if (data.favorited) {
                    favBtn.classList.add('active');
                    heart.className = 'fas fa-heart';
                    heart.style.color = '#ef4444';
                    txt.textContent = 'Guardado en Favoritos';
                    favBtn.style.background = 'rgba(239, 68, 68, 0.1)';
                    favBtn.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                    favBtn.style.boxShadow = '0 0 12px rgba(239, 68, 68, 0.15)';
                } else {
                    favBtn.classList.remove('active');
                    heart.className = 'far fa-heart';
                    heart.style.color = 'inherit';
                    txt.textContent = 'Añadir a Favoritos';
                    favBtn.style.background = 'rgba(255, 255, 255, 0.03)';
                    favBtn.style.borderColor = 'rgba(255, 255, 255, 0.08)';
                    favBtn.style.boxShadow = 'none';
                }
            }
        } catch (err) {
            console.error(err);
        } finally {
            favBtn.disabled = false;
        }
    });
}

// Interactive Star Ratings controller
const stars = document.querySelectorAll('.stars-container i');
const statusLbl = document.getElementById('rating-status-lbl');
if (stars.length > 0) {
    stars.forEach(star => {
        star.addEventListener('mouseover', () => {
            const val = parseInt(star.dataset.val);
            stars.forEach((s, idx) => {
                if (idx < val) {
                    s.style.color = 'var(--warning)';
                    s.className = 'fas fa-star';
                } else {
                    s.style.color = 'var(--text-muted)';
                    s.className = 'far fa-star';
                }
            });
        });
        
        star.addEventListener('mouseleave', () => {
            stars.forEach(s => {
                s.style.color = '';
                s.className = s.classList.contains('fas') ? 'fas fa-star' : 'far fa-star';
            });
        });
        
        star.addEventListener('click', async () => {
            const val = parseInt(star.dataset.val);
            const fd = new FormData();
            fd.append('recipe_id', RECIPE_ID);
            fd.append('rating', val);
            
            statusLbl.textContent = 'Guardando...';
            try {
                const res = await fetch(BASE_URL + '/api/rate.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    statusLbl.textContent = '¡Valorado con ' + val + '★!';
                    stars.forEach((s, idx) => {
                        if (idx < val) {
                            s.classList.replace('far', 'fas');
                            s.style.color = 'var(--warning)';
                        } else {
                            s.classList.replace('fas', 'far');
                            s.style.color = 'var(--text-muted)';
                        }
                    });
                    setTimeout(() => statusLbl.textContent = '', 2500);
                } else {
                    statusLbl.textContent = data.error || 'Error al guardar.';
                }
            } catch (err) {
                console.error(err);
                statusLbl.textContent = 'Error de conexión.';
            }
        });
    });
}

// Dynamic AJAX comment submission
const commentForm = document.getElementById('comment-form');
if (commentForm) {
    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const textNode = document.getElementById('comment-text');
        const comment = textNode.value.trim();
        if (!comment) return;
        
        const submitBtn = commentForm.querySelector('button');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        
        const fd = new FormData();
        fd.append('recipe_id', RECIPE_ID);
        fd.append('comment', comment);
        
        try {
            const res = await fetch(BASE_URL + '/api/comment.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                textNode.value = '';
                
                const noCommentsMsg = document.getElementById('no-comments-msg');
                if (noCommentsMsg) noCommentsMsg.remove();
                
                const container = document.getElementById('comments-container');
                const div = document.createElement('div');
                div.className = 'comment-item';
                div.style.background = 'var(--bg-card)';
                div.style.border = '1px solid var(--border-glass)';
                div.style.borderRadius = 'var(--radius-sm)';
                div.style.padding = '18px';
                div.style.display = 'flex';
                div.style.gap = '16px';
                div.style.opacity = '0';
                div.style.transform = 'translateY(-15px)';
                div.style.transition = 'all 0.5s ease';
                
                const initial = data.username ? data.username.charAt(0).toUpperCase() : 'U';
                div.innerHTML = `
                    <div class="comment-avatar" style="width:38px; height:38px; border-radius:50%; background:var(--gradient); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:0.9rem; flex-shrink:0;">
                        ${initial}
                    </div>
                    <div style="flex:1">
                        <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:6px;">
                            <strong style="font-size:0.9rem; color:var(--text-primary);">${data.username}</strong>
                            <small style="font-size:0.75rem; color:var(--text-muted);">Hace un momento</small>
                        </div>
                        <p style="font-size:0.88rem; color:var(--text-secondary); line-height:1.6; margin:0;">${sanitizeHTML(comment)}</p>
                    </div>
                `;
                
                container.insertBefore(div, container.firstChild);
                
                setTimeout(() => {
                    div.style.opacity = '1';
                    div.style.transform = 'translateY(0)';
                }, 50);
            } else {
                alert(data.error || 'No se pudo publicar el comentario.');
            }
        } catch (err) {
            console.error(err);
            alert('Error de red al publicar el comentario.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Comentario';
        }
    });
}

// Simple HTML sanitizer for dynamically added comment content
function sanitizeHTML(str) {
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
