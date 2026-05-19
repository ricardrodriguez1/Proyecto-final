<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

// Core stats
$totalUsers   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRecipes = $db->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
$totalFavs    = $db->query("SELECT COUNT(*) FROM favorites")->fetchColumn();
$totalComments= $db->query("SELECT COUNT(*) FROM recipe_comments")->fetchColumn();
$totalPlans   = $db->query("SELECT COUNT(*) FROM user_plans")->fetchColumn();
$totalRatings = $db->query("SELECT COUNT(*) FROM recipe_ratings")->fetchColumn();

// Recipe of the day (deterministic by date)
$dayOfYear = date('z');
$spotlightId = ($dayOfYear % max(1, $totalRecipes)) + 1;
$spotlight = $db->prepare("SELECT r.*, COALESCE(AVG(rr.rating),0) as avg_rating, COUNT(DISTINCT rr.id) as rating_count, COUNT(DISTINCT f.id) as fav_count FROM recipes r LEFT JOIN recipe_ratings rr ON r.id=rr.recipe_id LEFT JOIN favorites f ON r.id=f.recipe_id WHERE r.id >= :sid GROUP BY r.id LIMIT 1");
$spotlight->execute([':sid' => $spotlightId]);
$spotlightRecipe = $spotlight->fetch();

// Leaderboards
$topRated = $db->query("SELECT r.*, AVG(rr.rating) as avg_rating, COUNT(DISTINCT rr.id) as rating_count, COUNT(DISTINCT f.id) as fav_count FROM recipes r LEFT JOIN recipe_ratings rr ON r.id=rr.recipe_id LEFT JOIN favorites f ON r.id=f.recipe_id GROUP BY r.id HAVING rating_count>0 ORDER BY avg_rating DESC, rating_count DESC LIMIT 6")->fetchAll();

$mostFaved = $db->query("SELECT r.*, COUNT(f.id) as fav_count FROM recipes r JOIN favorites f ON r.id=f.recipe_id GROUP BY r.id ORDER BY fav_count DESC LIMIT 6")->fetchAll();

// Recent comments feed
$recentComments = $db->query("SELECT rc.*, u.username, r.title as recipe_title, r.id as recipe_id, r.image_url FROM recipe_comments rc JOIN users u ON rc.user_id=u.id JOIN recipes r ON rc.recipe_id=r.id ORDER BY rc.created_at DESC LIMIT 8")->fetchAll();

// Top users
$topUsers = $db->query("SELECT u.username, COUNT(DISTINCT f.id)+COUNT(DISTINCT rc.id)+COUNT(DISTINCT rr.id) as score, COUNT(DISTINCT f.id) as favs, COUNT(DISTINCT rc.id) as comments, COUNT(DISTINCT rr.id) as ratings FROM users u LEFT JOIN favorites f ON u.id=f.user_id LEFT JOIN recipe_comments rc ON u.id=rc.user_id LEFT JOIN recipe_ratings rr ON u.id=rr.user_id GROUP BY u.id HAVING score>0 ORDER BY score DESC LIMIT 5")->fetchAll();

// Explore: recent recipes with fav info
$myFavIds = [];
if (isLoggedIn()) {
    $stmtF = $db->prepare("SELECT recipe_id FROM favorites WHERE user_id = :uid");
    $stmtF->execute([':uid' => $_SESSION['user_id']]);
    $myFavIds = $stmtF->fetchAll(PDO::FETCH_COLUMN);
}
$exploreRecipes = $db->query("SELECT r.*, COUNT(DISTINCT f.id) as fav_count, COALESCE(AVG(rr.rating),0) as avg_rating FROM recipes r LEFT JOIN favorites f ON r.id=f.recipe_id LEFT JOIN recipe_ratings rr ON r.id=rr.recipe_id GROUP BY r.id ORDER BY RAND() LIMIT 8")->fetchAll();

// Category distribution
$categoryStats = $db->query("SELECT category, COUNT(*) as cnt FROM recipes GROUP BY category ORDER BY cnt DESC")->fetchAll();

$pageTitle = 'Comunidad';
include __DIR__ . '/includes/header.php';
?>
<style>
.comm-hero{background:linear-gradient(135deg,rgba(16,185,129,.07),rgba(6,182,212,.05));border-bottom:1px solid var(--border-glass);padding:60px 0 50px;text-align:center;position:relative;overflow:hidden}
.comm-hero::before{content:'';position:absolute;top:-80px;left:50%;transform:translateX(-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(16,185,129,.07),transparent 65%);pointer-events:none}
.comm-hero h1{font-size:2.8rem;font-weight:900;letter-spacing:-1px;margin-bottom:12px}
.comm-hero p{color:var(--text-secondary);max-width:540px;margin:0 auto 36px;font-size:1.05rem}
.c-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;max-width:860px;margin:0 auto}
.c-stat{background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius);padding:18px;text-align:center;backdrop-filter:blur(10px);transition:var(--transition)}
.c-stat:hover{transform:translateY(-3px);background:var(--bg-card-hover)}
.c-stat-num{font-size:1.9rem;font-weight:900;background:var(--gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.c-stat-lbl{font-size:.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:4px}
.comm-body{padding:60px 0}
.comm-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px}
@media(max-width:900px){.comm-grid{grid-template-columns:1fr}}
.sec-head{display:flex;align-items:center;gap:10px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border-glass)}
.sec-head h2{font-size:1.2rem;font-weight:800}
.lb-card{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-sm);margin-bottom:8px;transition:var(--transition);text-decoration:none;color:inherit}
.lb-card:hover{background:var(--bg-card-hover);transform:translateX(4px);color:inherit}
.lb-rank{font-size:1.1rem;font-weight:900;min-width:32px;text-align:center;color:var(--text-muted)}
.lb-thumb{width:52px;height:52px;border-radius:8px;overflow:hidden;flex-shrink:0}
.lb-thumb img{width:100%;height:100%;object-fit:cover}
.lb-info{flex:1;overflow:hidden}
.lb-title{font-size:.88rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lb-sub{font-size:.75rem;color:var(--text-muted);margin-top:3px;display:flex;gap:8px}
.lb-score{font-size:.95rem;font-weight:800;color:var(--accent-light);white-space:nowrap}
.act-feed{display:flex;flex-direction:column;gap:10px}
.act-item{display:flex;gap:12px;padding:12px;background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-sm);transition:var(--transition)}
.act-item:hover{background:var(--bg-card-hover)}
.act-av{width:38px;height:38px;border-radius:50%;background:var(--gradient);display:flex;align-items:center;justify-content:center;font-size:.9rem;color:#fff;font-weight:700;flex-shrink:0}
.act-body{flex:1}
.act-user{font-size:.85rem;font-weight:700}
.act-text{font-size:.8rem;color:var(--text-secondary)}
.act-text a{color:var(--accent-light)}
.act-time{font-size:.7rem;color:var(--text-muted);margin-top:3px}
.act-img{width:42px;height:42px;border-radius:8px;overflow:hidden;flex-shrink:0}
.act-img img{width:100%;height:100%;object-fit:cover}
.u-row{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-sm);margin-bottom:8px;transition:var(--transition)}
.u-row:hover{background:var(--bg-card-hover)}
.u-av{width:44px;height:44px;border-radius:50%;background:var(--gradient);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;font-weight:800;flex-shrink:0}
.u-badges{display:flex;gap:5px;flex-wrap:wrap;margin-top:4px}
.ubadge{font-size:.65rem;padding:2px 7px;border-radius:10px;font-weight:700}
.ubadge.fav{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.15)}
.ubadge.cmnt{background:rgba(6,182,212,.1);color:var(--accent2);border:1px solid rgba(6,182,212,.15)}
.ubadge.star{background:rgba(245,158,11,.1);color:var(--warning);border:1px solid rgba(245,158,11,.15)}
.u-score{font-size:1.1rem;font-weight:900;color:var(--accent-light)}
.join-cta{margin-top:18px;padding:18px;background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.15);border-radius:var(--radius-sm);text-align:center}
</style>

<section class="comm-hero">
  <div class="container">
    <h1><i class="fas fa-globe-americas" style="color:var(--accent)"></i> Comunidad NutriFit</h1>
    <p>Descubre qué están cocinando otros, las recetas más valoradas y conéctate con la comunidad saludable más activa.</p>
    <div class="c-stats">
      <div class="c-stat"><div class="c-stat-num"><?= $totalUsers ?></div><div class="c-stat-lbl">Miembros</div></div>
      <div class="c-stat"><div class="c-stat-num"><?= $totalRecipes ?></div><div class="c-stat-lbl">Recetas</div></div>
      <div class="c-stat"><div class="c-stat-num"><?= $totalFavs ?></div><div class="c-stat-lbl">Favoritos</div></div>
      <div class="c-stat"><div class="c-stat-num"><?= $totalComments ?></div><div class="c-stat-lbl">Comentarios</div></div>
      <div class="c-stat"><div class="c-stat-num"><?= $totalRatings ?></div><div class="c-stat-lbl">Valoraciones</div></div>
      <div class="c-stat"><div class="c-stat-num"><?= $totalPlans ?></div><div class="c-stat-lbl">Planes</div></div>
    </div>
  </div>
</section>

<!-- SPOTLIGHT: Recipe of the Day -->
<?php if ($spotlightRecipe): ?>
<section style="padding:40px 0 0">
  <div class="container">
    <div class="sec-head"><i class="fas fa-sun" style="color:var(--warning)"></i><h2><i class="fas fa-utensils"></i> Receta del Día</h2></div>
    <a href="<?= baseUrl() ?>/recipes/detail.php?id=<?= $spotlightRecipe['id'] ?>" style="text-decoration:none;color:inherit;display:grid;grid-template-columns:1fr 1fr;gap:30px;background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius);overflow:hidden;transition:var(--transition)" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
      <div style="height:280px;overflow:hidden">
        <img src="<?= sanitize($spotlightRecipe['image_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover" loading="lazy">
      </div>
      <div style="padding:30px 30px 30px 0;display:flex;flex-direction:column;justify-content:center">
        <div style="display:flex;gap:8px;margin-bottom:12px">
          <span class="tag"><?= sanitize($spotlightRecipe['category']) ?></span>
          <span class="tag"><?= sanitize($spotlightRecipe['diet_type']) ?></span>
          <span class="tag"><?= sanitize($spotlightRecipe['difficulty']) ?></span>
        </div>
        <h3 style="font-size:1.6rem;font-weight:900;margin-bottom:10px"><?= sanitize($spotlightRecipe['title']) ?></h3>
        <p style="color:var(--text-secondary);line-height:1.7;margin-bottom:16px;font-size:.92rem"><?= sanitize($spotlightRecipe['description']) ?></p>
        <div style="display:flex;gap:20px;font-size:.85rem;color:var(--text-muted)">
          <span><i class="fas fa-clock"></i> <?= $spotlightRecipe['prep_time']+$spotlightRecipe['cook_time'] ?> min</span>
          <span><i class="fas fa-fire"></i> <?= $spotlightRecipe['calories'] ?> kcal</span>
          <span><i class="fas fa-heart" style="color:#f87171"></i> <?= $spotlightRecipe['fav_count'] ?></span>
          <?php if($spotlightRecipe['avg_rating']>0): ?><span><i class="fas fa-star" style="color:var(--warning)"></i> <?= number_format($spotlightRecipe['avg_rating'],1) ?></span><?php endif; ?>
        </div>
      </div>
    </a>
  </div>
</section>
<?php endif; ?>

<!-- Category Distribution -->
<section style="padding:40px 0 0">
  <div class="container">
    <div class="sec-head"><i class="fas fa-chart-pie" style="color:var(--accent2)"></i><h2>Distribución por Categoría</h2></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">
      <?php 
      $catIcons = [
          'desayuno' => '<i class="fas fa-sun" style="color:#f59e0b"></i>',
          'comida'   => '<i class="fas fa-utensils" style="color:#10b981"></i>',
          'cena'     => '<i class="fas fa-moon" style="color:#8b5cf6"></i>',
          'snack'    => '<i class="fas fa-apple-alt" style="color:#ef4444"></i>'
      ];
      $catColors = ['desayuno'=>'#f59e0b','comida'=>'#10b981','cena'=>'#8b5cf6','snack'=>'#ef4444'];
      foreach($categoryStats as $cs): 
        $icon = $catIcons[$cs['category']] ?? '<i class="fas fa-utensils"></i>';
        $color = $catColors[$cs['category']] ?? 'var(--accent)';
        $pct = round(($cs['cnt']/$totalRecipes)*100);
      ?>
      <div style="background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-sm);padding:20px;text-align:center">
        <div style="font-size:2rem;margin-bottom:6px"><?= $icon ?></div>
        <div style="font-weight:800;text-transform:capitalize;margin-bottom:8px"><?= sanitize($cs['category']) ?></div>
        <div style="background:rgba(255,255,255,.05);border-radius:20px;height:8px;overflow:hidden;margin-bottom:6px">
          <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:20px;transition:width 1s ease"></div>
        </div>
        <div style="font-size:.78rem;color:var(--text-muted)"><?= $cs['cnt'] ?> recetas (<?= $pct ?>%)</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
  <div class="container">
    <div class="comm-grid">

      <!-- TOP RATED -->
      <div>
        <div class="sec-head"><i class="fas fa-star" style="color:var(--warning)"></i><h2>Mejor valoradas</h2></div>
        <?php if (empty($topRated)): ?>
          <p style="color:var(--text-muted);font-size:.9rem">¡Sé el primero en valorar una receta!</p>
        <?php else: foreach ($topRated as $i => $r): 
          $medals = [
              '<i class="fas fa-medal" style="color:#ffd700"></i>',
              '<i class="fas fa-medal" style="color:#c0c0c0"></i>',
              '<i class="fas fa-medal" style="color:#cd7f32"></i>'
          ];
        ?>
          <a href="<?= baseUrl() ?>/recipes/detail.php?id=<?= $r['id'] ?>" class="lb-card">
            <div class="lb-rank"><?= $i<3 ? $medals[$i] : '#'.($i+1) ?></div>
            <div class="lb-thumb"><img src="<?= sanitize($r['image_url']) ?>" alt="" loading="lazy"></div>
            <div class="lb-info">
              <div class="lb-title"><?= sanitize($r['title']) ?></div>
              <div class="lb-sub"><span><i class="fas fa-heart" style="color:#f87171"></i> <?= $r['fav_count'] ?></span><span><i class="fas fa-star" style="color:var(--warning)"></i> <?= $r['rating_count'] ?> votos</span></div>
            </div>
            <div class="lb-score"><?= number_format($r['avg_rating'],1) ?> <i class="fas fa-star" style="color:var(--warning)"></i></div>
          </a>
        <?php endforeach; endif; ?>
      </div>

      <!-- MOST FAVORITED -->
      <div>
        <div class="sec-head"><i class="fas fa-heart" style="color:#f87171"></i><h2>Más guardadas</h2></div>
        <?php if (empty($mostFaved)): ?>
          <p style="color:var(--text-muted);font-size:.9rem">¡Empieza a guardar tus recetas favoritas!</p>
        <?php else: foreach ($mostFaved as $i => $r): 
          $medals = [
              '<i class="fas fa-medal" style="color:#ffd700"></i>',
              '<i class="fas fa-medal" style="color:#c0c0c0"></i>',
              '<i class="fas fa-medal" style="color:#cd7f32"></i>'
          ];
        ?>
          <a href="<?= baseUrl() ?>/recipes/detail.php?id=<?= $r['id'] ?>" class="lb-card">
            <div class="lb-rank"><?= $i<3 ? $medals[$i] : '#'.($i+1) ?></div>
            <div class="lb-thumb"><img src="<?= sanitize($r['image_url']) ?>" alt="" loading="lazy"></div>
            <div class="lb-info">
              <div class="lb-title"><?= sanitize($r['title']) ?></div>
              <div class="lb-sub"><span><?= sanitize($r['category']) ?></span><span><?= sanitize($r['diet_type']) ?></span></div>
            </div>
            <div class="lb-score" style="color:#f87171"><i class="fas fa-heart"></i> <?= $r['fav_count'] ?></div>
          </a>
        <?php endforeach; endif; ?>
      </div>

      <!-- ACTIVITY FEED -->
      <div>
        <div class="sec-head"><i class="fas fa-stream" style="color:var(--accent2)"></i><h2>Actividad reciente</h2></div>
        <?php if (empty($recentComments)): ?>
          <p style="color:var(--text-muted);font-size:.9rem">¡Comenta una receta y empieza el movimiento!</p>
        <?php else: ?>
          <div class="act-feed">
            <?php foreach ($recentComments as $act): ?>
              <div class="act-item">
                <div class="act-av"><?= strtoupper(substr($act['username'],0,1)) ?></div>
                <div class="act-body">
                  <div class="act-user"><?= sanitize($act['username']) ?></div>
                  <div class="act-text">Comentó en <a href="<?= baseUrl() ?>/recipes/detail.php?id=<?= $act['recipe_id'] ?>"><?= sanitize($act['recipe_title']) ?></a>: <em>"<?= sanitize(mb_substr($act['comment'],0,70)) ?><?= mb_strlen($act['comment'])>70?'…':'' ?>"</em></div>
                  <div class="act-time"><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($act['created_at'])) ?></div>
                </div>
                <div class="act-img"><img src="<?= sanitize($act['image_url']) ?>" alt="" loading="lazy"></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- TOP USERS -->
      <div>
        <div class="sec-head"><i class="fas fa-trophy" style="color:var(--warning)"></i><h2>Usuarios más activos</h2></div>
        <?php if (empty($topUsers)): ?>
          <p style="color:var(--text-muted);font-size:.9rem">¡Sé el primer usuario activo!</p>
        <?php else: 
          $trophies = [
              '<i class="fas fa-trophy" style="color:#ffd700"></i>',
              '<i class="fas fa-medal" style="color:#c0c0c0"></i>',
              '<i class="fas fa-medal" style="color:#cd7f32"></i>',
              '#4',
              '#5'
          ]; 
          foreach ($topUsers as $i => $u): 
        ?>
          <div class="u-row">
            <div class="u-av"><?= strtoupper(substr($u['username'],0,1)) ?></div>
            <div style="flex:1">
              <div style="font-weight:700;font-size:.92rem"><?= $trophies[$i] ?> <?= sanitize($u['username']) ?></div>
              <div class="u-badges">
                <?php if($u['favs']>0):?><span class="ubadge fav"><i class="fas fa-heart"></i> <?=$u['favs']?></span><?php endif;?>
                <?php if($u['comments']>0):?><span class="ubadge cmnt"><i class="fas fa-comment"></i> <?=$u['comments']?></span><?php endif;?>
                <?php if($u['ratings']>0):?><span class="ubadge star"><i class="fas fa-star"></i> <?=$u['ratings']?></span><?php endif;?>
              </div>
            </div>
            <div class="u-score"><?= $u['score'] ?></div>
          </div>
        <?php endforeach; endif; ?>
        <?php if (!isLoggedIn()): ?>
          <div class="join-cta">
            <p style="margin-bottom:10px;color:var(--text-secondary);font-size:.88rem">¡Únete para aparecer en el ranking!</p>
            <a href="<?= baseUrl() ?>/auth/register.php" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Registrarse gratis</a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- EXPLORE RECIPES -->
<section style="padding:50px 0">
  <div class="container">
    <div class="sec-head"><i class="fas fa-compass" style="color:var(--accent)"></i><h2>Explora Recetas de la Comunidad</h2></div>
    <p style="color:var(--text-secondary);margin-bottom:24px;font-size:.9rem">Descubre recetas al azar y guarda tus favoritas con un clic.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px">
      <?php foreach($exploreRecipes as $er): 
        $isFav = in_array($er['id'], $myFavIds);
      ?>
      <div class="recipe-card" style="position:relative">
        <div class="recipe-card-image" style="position:relative;overflow:hidden">
          <a href="<?= baseUrl() ?>/recipes/detail.php?id=<?= $er['id'] ?>">
            <img src="<?= sanitize($er['image_url']) ?>" alt="<?= sanitize($er['title']) ?>" loading="lazy" style="width:100%;height:180px;object-fit:cover">
          </a>
          <span class="recipe-card-badge"><?= sanitize($er['category']) ?></span>
          <?php if(isLoggedIn()): ?>
          <button class="fav-toggle-btn <?= $isFav?'active':'' ?>" data-id="<?= $er['id'] ?>" style="position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:50%;background:<?= $isFav?'rgba(239,68,68,.15)':'rgba(11,15,25,.6)' ?>;backdrop-filter:blur(8px);border:1px solid <?= $isFav?'rgba(239,68,68,.3)':'rgba(255,255,255,.1)' ?>;color:<?= $isFav?'#ef4444':'#fff' ?>;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .3s cubic-bezier(.175,.885,.32,1.275);z-index:10;outline:none">
            <i class="<?= $isFav?'fas':'far' ?> fa-heart"></i>
          </button>
          <?php endif; ?>
        </div>
        <a href="<?= baseUrl() ?>/recipes/detail.php?id=<?= $er['id'] ?>" style="text-decoration:none;color:inherit;display:block;padding:14px 0 0">
          <h3 style="font-size:.95rem;font-weight:700;margin-bottom:4px"><?= sanitize($er['title']) ?></h3>
          <div style="display:flex;gap:12px;font-size:.75rem;color:var(--text-muted)">
            <span><i class="fas fa-fire"></i> <?= $er['calories'] ?> kcal</span>
            <span><i class="fas fa-heart" style="color:#f87171"></i> <?= $er['fav_count'] ?></span>
            <?php if($er['avg_rating']>0): ?><span><i class="fas fa-star" style="color:var(--warning)"></i> <?= number_format($er['avg_rating'],1) ?></span><?php endif; ?>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:30px">
      <a href="<?= baseUrl() ?>/recipes/index.php" class="btn btn-primary"><i class="fas fa-utensils"></i> Ver Todas las Recetas</a>
    </div>
  </div>
</section>

<script>
document.querySelectorAll('.fav-toggle-btn').forEach(btn => {
  btn.addEventListener('click', async (e) => {
    e.preventDefault(); e.stopPropagation();
    const fd = new FormData(); fd.append('recipe_id', btn.dataset.id);
    btn.disabled = true; btn.style.transform = 'scale(0.8)';
    try {
      const res = await fetch('<?= baseUrl() ?>/api/favorite.php', {method:'POST',body:fd});
      const data = await res.json();
      if (data.success) {
        if (data.favorited) {
          btn.classList.add('active'); btn.style.color='#ef4444';
          btn.style.background='rgba(239,68,68,.15)'; btn.innerHTML='<i class="fas fa-heart"></i>';
        } else {
          btn.classList.remove('active'); btn.style.color='#fff';
          btn.style.background='rgba(11,15,25,.6)'; btn.innerHTML='<i class="far fa-heart"></i>';
        }
        btn.style.transform='scale(1.25)';
        setTimeout(()=>{btn.style.transform='scale(1)';btn.disabled=false},200);
      }
    } catch(err) { console.error(err); btn.disabled=false; btn.style.transform='scale(1)'; }
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
