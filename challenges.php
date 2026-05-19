<?php
require_once __DIR__ . '/includes/functions.php';

// Pre-defined challenges definition
$challenges = [
    'vegan_week'       => ['icon'=>'fas fa-leaf','title'=>'Semana Vegana','desc'=>'Cocina o guarda en favoritos 7 recetas veganas esta semana.','target'=>7,'color'=>'#10b981','category'=>'Dieta'],
    'low_cal_snack'    => ['icon'=>'fas fa-apple-alt','title'=>'Snack Consciente','desc'=>'Explora 3 snacks de menos de 200 kcal.','target'=>3,'color'=>'#06b6d4','category'=>'Calorías'],
    'protein_boost'    => ['icon'=>'fas fa-dumbbell','title'=>'Proteína al Máximo','desc'=>'Guarda 5 recetas marcadas como "alto-proteina" en favoritos.','target'=>5,'color'=>'#3b82f6','category'=>'Macros'],
    'explore_five'     => ['icon'=>'fas fa-map-marked-alt','title'=>'Gran Explorador','desc'=>'Visita y valora 5 recetas de categorías distintas.','target'=>5,'color'=>'#8b5cf6','category'=>'Exploración'],
    'plan_generator'   => ['icon'=>'fas fa-bolt','title'=>'Primer Plan','desc'=>'Genera tu primer plan nutricional personalizado en la calculadora.','target'=>1,'color'=>'#f59e0b','category'=>'Calculadora'],
    'breakfast_streak' => ['icon'=>'fas fa-sun','title'=>'Ritual de Mañana','desc'=>'Guarda 7 recetas de desayuno en tus favoritos.','target'=>7,'color'=>'#ef4444','category'=>'Desayuno'],
];

$userProgress = [];
if (isLoggedIn()) {
    $db = getDB();
    $stmt = $db->prepare("SELECT challenge_key, progress, completed, joined_at, completed_at FROM user_challenges WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    foreach ($stmt->fetchAll() as $row) {
        $userProgress[$row['challenge_key']] = $row;
    }
}

$pageTitle = 'Retos';
include __DIR__ . '/includes/header.php';
?>
<style>
.challenges-hero{padding:60px 0 50px;text-align:center;position:relative}
.challenges-hero::before{content:'';position:absolute;top:-60px;left:50%;transform:translateX(-50%);width:700px;height:500px;background:radial-gradient(circle,rgba(245,158,11,.06),transparent 65%);pointer-events:none}
.challenges-hero h1{font-size:2.6rem;font-weight:900;letter-spacing:-1px;margin-bottom:12px}
.challenges-hero p{color:var(--text-secondary);max-width:520px;margin:0 auto;font-size:1rem}
.challenges-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;padding:40px 0 80px}
.challenge-card{background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius);padding:28px;transition:var(--transition);position:relative;overflow:hidden}
.challenge-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;transition:var(--transition);opacity:0}
.challenge-card:hover{background:var(--bg-card-hover);transform:translateY(-5px);box-shadow:var(--shadow-glow)}
.challenge-card:hover::before{opacity:1}
.challenge-card.joined::before{opacity:1}
.ch-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px}
.ch-icon{font-size:2.5rem;line-height:1}
.ch-cat{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:3px 10px;border-radius:10px;background:rgba(255,255,255,.05);color:var(--text-muted)}
.ch-title{font-size:1.1rem;font-weight:800;margin-bottom:6px}
.ch-desc{font-size:.85rem;color:var(--text-secondary);line-height:1.6;margin-bottom:20px}
.ch-progress-wrap{margin-bottom:16px}
.ch-progress-label{display:flex;justify-content:space-between;font-size:.78rem;color:var(--text-muted);margin-bottom:6px}
.ch-bar{height:8px;background:rgba(255,255,255,.05);border-radius:8px;overflow:hidden}
.ch-bar-fill{height:100%;border-radius:8px;transition:width 1s cubic-bezier(.1,.8,.2,1)}
.ch-actions{display:flex;gap:10px;align-items:center}
.ch-completed-badge{display:flex;align-items:center;gap:8px;font-size:.85rem;font-weight:700;color:#10b981;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);border-radius:var(--radius-sm);padding:8px 14px;width:100%;justify-content:center}
.ch-stats-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:40px}
.ch-stat-box{background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius);padding:20px 24px;display:flex;align-items:center;gap:16px}
.ch-stat-icon{font-size:1.8rem}
.ch-stat-num{font-size:1.6rem;font-weight:900;color:var(--text-primary)}
.ch-stat-lbl{font-size:.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.not-joined-overlay{opacity:.7}
</style>

<div class="challenges-hero">
  <div class="container">
    <h1><i class="fas fa-trophy" style="color:var(--accent)"></i> Retos NutriFit</h1>
    <p>Pon a prueba tu compromiso con la alimentación saludable. Completa retos, gana insignias y sube en el ranking de la comunidad.</p>
  </div>
</div>

<div class="container">

  <?php if (isLoggedIn()):
    $joined    = count($userProgress);
    $completed = count(array_filter($userProgress, fn($p) => $p['completed']));
  ?>
  <!-- User stats bar -->
  <div class="ch-stats-bar">
    <div class="ch-stat-box">
      <div class="ch-stat-icon" style="color:#f59e0b"><i class="fas fa-bullseye"></i></div>
      <div><div class="ch-stat-num"><?= $joined ?></div><div class="ch-stat-lbl">Retos iniciados</div></div>
    </div>
    <div class="ch-stat-box">
      <div class="ch-stat-icon" style="color:#10b981"><i class="fas fa-check-circle"></i></div>
      <div><div class="ch-stat-num"><?= $completed ?></div><div class="ch-stat-lbl">Retos completados</div></div>
    </div>
    <div class="ch-stat-box">
      <div class="ch-stat-icon" style="color:#ef4444"><i class="fas fa-fire"></i></div>
      <div><div class="ch-stat-num"><?= count($challenges) - $joined ?></div><div class="ch-stat-lbl">Pendientes de unirte</div></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="challenges-grid">
    <?php foreach ($challenges as $key => $ch):
      $p        = $userProgress[$key] ?? null;
      $isJoined = !is_null($p);
      $isDone   = $isJoined && $p['completed'];
      $progress = $isJoined ? intval($p['progress']) : 0;
      $pct      = $ch['target'] > 0 ? min(100, round($progress / $ch['target'] * 100)) : 0;
    ?>
    <div class="challenge-card <?= $isJoined ? 'joined' : 'not-joined-overlay' ?>"
         style="--card-color:<?= $ch['color'] ?>"
         id="ch-card-<?= $key ?>">
      <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?= $ch['color'] ?>;opacity:<?= $isJoined?1:.3 ?>"></div>

      <div class="ch-top">
        <div class="ch-icon" style="color:<?= $ch['color'] ?>"><i class="<?= $ch['icon'] ?>"></i></div>
        <span class="ch-cat" style="color:<?= $ch['color'] ?>;background:<?= $ch['color'] ?>18;border:1px solid <?= $ch['color'] ?>30"><?= $ch['category'] ?></span>
      </div>

      <div class="ch-title"><?= $ch['title'] ?></div>
      <div class="ch-desc"><?= $ch['desc'] ?></div>

      <!-- Progress bar -->
      <?php if ($isJoined): ?>
      <div class="ch-progress-wrap">
        <div class="ch-progress-label">
          <span><?= $isDone ? '¡Completado!' : 'Progreso' ?></span>
          <span id="ch-prog-txt-<?= $key ?>"><?= $progress ?>/<?= $ch['target'] ?></span>
        </div>
        <div class="ch-bar">
          <div class="ch-bar-fill" id="ch-bar-<?= $key ?>" style="width:<?= $pct ?>%;background:<?= $ch['color'] ?>"></div>
        </div>
      </div>
      <?php endif; ?>

      <div class="ch-actions">
        <?php if ($isDone): ?>
          <div class="ch-completed-badge"><i class="fas fa-check-circle"></i> ¡Reto completado!</div>
        <?php elseif (!isLoggedIn()): ?>
          <a href="<?= baseUrl() ?>/auth/login.php" class="btn btn-outline btn-sm"><i class="fas fa-sign-in-alt"></i> Inicia sesión para participar</a>
        <?php elseif (!$isJoined): ?>
          <button class="btn btn-primary btn-sm ch-join-btn" data-key="<?= $key ?>" style="background:<?= $ch['color'] ?>;box-shadow:0 4px 15px <?= $ch['color'] ?>30">
            <i class="fas fa-flag"></i> Unirse al reto
          </button>
        <?php else: ?>
          <button class="btn btn-sm ch-progress-btn" data-key="<?= $key ?>" style="background:<?= $ch['color'] ?>20;color:<?= $ch['color'] ?>;border:1px solid <?= $ch['color'] ?>40">
            <i class="fas fa-plus"></i> Marcar progreso (+1)
          </button>
          <span style="font-size:.75rem;color:var(--text-muted)">Desde <?= date('d/m', strtotime($p['joined_at'])) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<script>
const BASE = '<?= baseUrl() ?>';

// Join challenge
document.querySelectorAll('.ch-join-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const key = btn.dataset.key;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uniéndose...';
    const fd = new FormData();
    fd.append('action','join'); fd.append('challenge_key', key);
    const res = await fetch(BASE+'/api/challenge.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      const card = document.getElementById('ch-card-'+key);
      // Reload to show progress UI
      location.reload();
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-flag"></i> Unirse al reto';
    }
  });
});

// Mark progress
document.querySelectorAll('.ch-progress-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const key = btn.dataset.key;
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action','progress'); fd.append('challenge_key', key); fd.append('increment','1');
    const res = await fetch(BASE+'/api/challenge.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      const pct = Math.min(100, Math.round(data.progress / data.target * 100));
      const bar = document.getElementById('ch-bar-'+key);
      const txt = document.getElementById('ch-prog-txt-'+key);
      if (bar) bar.style.width = pct+'%';
      if (txt) txt.textContent = data.progress+'/'+data.target;
      if (data.completed) {
        btn.replaceWith(Object.assign(document.createElement('div'), {
          className:'ch-completed-badge',
          innerHTML:'<i class="fas fa-check-circle"></i> ¡Reto completado!'
        }));
        const progressWrap = document.querySelector('#ch-card-'+key+' .ch-progress-label span:first-child');
        if (progressWrap) progressWrap.textContent = '¡Completado!';
      } else {
        btn.disabled = false;
      }
    } else {
      btn.disabled = false;
    }
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
