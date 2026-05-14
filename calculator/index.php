<?php
require_once __DIR__ . '/../includes/functions.php';

$result = null;
$plan = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $genero = $_POST['genero'] ?? 'male';
    $peso = floatval($_POST['peso'] ?? 70);
    $altura = floatval($_POST['altura'] ?? 170);
    $edad = intval($_POST['edad'] ?? 25);
    $actividad = $_POST['actividad'] ?? 'moderado';
    $objetivo = $_POST['objetivo'] ?? 'mantenimiento';
    $dietType = $_POST['diet_type'] ?? 'omnivoro';
    $planType = $_POST['plan_type'] ?? 'completo';
    $allergies = $_POST['allergies'] ?? [];

    $bmr = calculateBMR($genero, $peso, $altura, $edad);
    $tdee = calculateTDEE($bmr, $actividad);
    $targetCal = adjustCaloriesByObjective($tdee, $objetivo);

    // Macro distribution based on objective
    switch ($objetivo) {
        case 'volumen':
            $protPct = 0.25; $carbPct = 0.50; $fatPct = 0.25;
            break;
        case 'definicion':
            $protPct = 0.35; $carbPct = 0.40; $fatPct = 0.25;
            break;
        default:
            $protPct = 0.30; $carbPct = 0.45; $fatPct = 0.25;
    }

    $result = [
        'bmr' => round($bmr),
        'tdee' => $tdee,
        'target' => $targetCal,
        'protein' => round(($targetCal * $protPct) / 4),
        'carbs' => round(($targetCal * $carbPct) / 4),
        'fat' => round(($targetCal * $fatPct) / 9),
        'objetivo' => $objetivo,
    ];

    $plan = generateMealPlan($targetCal, $dietType, $allergies, $planType);
}

$pageTitle = 'Calculadora Nutricional';
include __DIR__ . '/../includes/header.php';
?>

<div class="calculator-page">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-calculator" style="color:var(--accent)"></i> Calculadora Nutricional</h1>
            <p>Calcula tu metabolismo, elige tu objetivo y genera un plan personalizado</p>
        </div>

        <?php if (!$result): ?>
        <!-- Calculator Form -->
        <div class="calc-container">
            <!-- Step Indicators -->
            <div class="calc-steps no-print">
                <div class="calc-step active"><span class="step-num">1</span> Datos</div>
                <div class="calc-step"><span class="step-num">2</span> Objetivo</div>
                <div class="calc-step"><span class="step-num">3</span> Preferencias</div>
                <div class="calc-step"><span class="step-num">4</span> Plan</div>
            </div>

            <form id="calcForm" method="POST" action="">
                <!-- STEP 1: Personal Data -->
                <div class="calc-step-content calc-card">
                    <h2><i class="fas fa-heartbeat" style="color:var(--accent)"></i> Tus datos personales</h2>
                    <p style="color:var(--text-secondary);margin-bottom:24px;font-size:0.9rem">Introduce tus datos para calcular tu metabolismo basal (BMR) y gasto calórico diario (TDEE).</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="genero">Género</label>
                            <select name="genero" id="genero" required>
                                <option value="">Seleccionar...</option>
                                <option value="male">Hombre</option>
                                <option value="female">Mujer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edad">Edad (años)</label>
                            <input type="number" name="edad" id="edad" min="14" max="100" placeholder="25" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="peso">Peso (kg)</label>
                            <input type="number" name="peso" id="peso" min="30" max="250" step="0.1" placeholder="70" required>
                        </div>
                        <div class="form-group">
                            <label for="altura">Altura (cm)</label>
                            <input type="number" name="altura" id="altura" min="100" max="250" placeholder="170" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="actividad">Nivel de actividad física</label>
                        <select name="actividad" id="actividad" required>
                            <option value="">Seleccionar...</option>
                            <option value="sedentario">Sedentario (poco o ningún ejercicio)</option>
                            <option value="ligero">Ligero (1-3 días/semana)</option>
                            <option value="moderado">Moderado (3-5 días/semana)</option>
                            <option value="activo">Activo (6-7 días/semana)</option>
                            <option value="muy_activo">Muy activo (atleta, trabajo físico)</option>
                        </select>
                    </div>
                    <div id="bmrPreview" style="display:none;padding:12px 16px;background:rgba(16,185,129,0.08);border-radius:var(--radius-sm);color:var(--accent-light);font-size:0.9rem;margin-bottom:16px;text-align:center"></div>
                    <div style="text-align:right">
                        <button type="button" class="btn btn-primary btn-next"><i class="fas fa-arrow-right"></i> Siguiente</button>
                    </div>
                </div>

                <!-- STEP 2: Objective -->
                <div class="calc-step-content calc-card" style="display:none">
                    <h2><i class="fas fa-bullseye" style="color:var(--accent)"></i> Tu objetivo</h2>
                    <p style="color:var(--text-secondary);margin-bottom:24px;font-size:0.9rem">¿Qué quieres conseguir con tu alimentación?</p>

                    <input type="hidden" name="objetivo" id="objetivo" value="">

                    <div class="objective-cards">
                        <div class="objective-card" data-value="volumen">
                            <i class="fas fa-dumbbell"></i>
                            <h4>Volumen</h4>
                            <p>Ganar masa muscular con superávit calórico (+15%)</p>
                        </div>
                        <div class="objective-card" data-value="mantenimiento">
                            <i class="fas fa-balance-scale"></i>
                            <h4>Mantenimiento</h4>
                            <p>Mantener tu peso actual con calorías equilibradas</p>
                        </div>
                        <div class="objective-card" data-value="definicion">
                            <i class="fas fa-fire-alt"></i>
                            <h4>Definición</h4>
                            <p>Perder grasa con déficit calórico controlado (-15%)</p>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:space-between">
                        <button type="button" class="btn btn-outline btn-prev"><i class="fas fa-arrow-left"></i> Anterior</button>
                        <button type="button" class="btn btn-primary btn-next"><i class="fas fa-arrow-right"></i> Siguiente</button>
                    </div>
                </div>

                <!-- STEP 3: Preferences -->
                <div class="calc-step-content calc-card" style="display:none">
                    <h2><i class="fas fa-sliders-h" style="color:var(--accent)"></i> Preferencias alimentarias</h2>
                    <p style="color:var(--text-secondary);margin-bottom:24px;font-size:0.9rem">Personaliza las recetas según tu dieta y posibles alergias.</p>

                    <div class="form-group">
                        <label>Tipo de dieta</label>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="radio" name="diet_type" value="omnivoro" checked> 🥩 Omnívoro
                            </label>
                            <label class="checkbox-item">
                                <input type="radio" name="diet_type" value="vegetariano"> 🥚 Vegetariano
                            </label>
                            <label class="checkbox-item">
                                <input type="radio" name="diet_type" value="vegano"> 🌱 Vegano
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Alergias / Intolerancias</label>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="allergies[]" value="gluten"> 🌾 Sin gluten
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="allergies[]" value="lactosa"> 🥛 Sin lactosa
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="allergies[]" value="frutos_secos"> 🥜 Sin frutos secos
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="allergies[]" value="mariscos"> 🦐 Sin mariscos
                            </label>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:space-between">
                        <button type="button" class="btn btn-outline btn-prev"><i class="fas fa-arrow-left"></i> Anterior</button>
                        <button type="button" class="btn btn-primary btn-next"><i class="fas fa-arrow-right"></i> Siguiente</button>
                    </div>
                </div>

                <!-- STEP 4: Plan Type -->
                <div class="calc-step-content calc-card" style="display:none">
                    <h2><i class="fas fa-clipboard-list" style="color:var(--accent)"></i> Tipo de plan</h2>
                    <p style="color:var(--text-secondary);margin-bottom:24px;font-size:0.9rem">¿Quieres una receta individual o un plan completo del día?</p>

                    <input type="hidden" name="plan_type" id="planType" value="completo">

                    <div class="objective-cards" style="grid-template-columns:1fr 1fr">
                        <div class="plan-type-card objective-card selected" data-value="completo">
                            <i class="fas fa-calendar-day"></i>
                            <h4>Plan completo</h4>
                            <p>Desayuno, comida, cena y snack. Día completo planificado.</p>
                        </div>
                        <div class="plan-type-card objective-card" data-value="receta">
                            <i class="fas fa-utensils"></i>
                            <h4>Solo una receta</h4>
                            <p>Una receta individual adaptada a tus calorías.</p>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:space-between">
                        <button type="button" class="btn btn-outline btn-prev"><i class="fas fa-arrow-left"></i> Anterior</button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-magic"></i> Generar mi plan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php else: ?>
        <!-- RESULTS -->
        <div class="calc-container">
            <div class="results-card animate-in">
                <h2 style="text-align:center;margin-bottom:24px">
                    <i class="fas fa-chart-bar" style="color:var(--accent)"></i> Tu resumen nutricional
                </h2>

                <div class="calorie-display">
                    <div class="number"><?= $result['target'] ?></div>
                    <div class="unit">kcal / día (objetivo: <?= $result['objetivo'] ?>)</div>
                    <p style="font-size:0.82rem;color:var(--text-muted);margin-top:8px">
                        BMR: <?= $result['bmr'] ?> kcal → TDEE: <?= $result['tdee'] ?> kcal
                    </p>
                </div>

                <div class="macro-grid">
                    <div class="macro-item">
                        <div class="macro-value"><?= $result['protein'] ?>g</div>
                        <div class="macro-label">Proteínas</div>
                    </div>
                    <div class="macro-item">
                        <div class="macro-value"><?= $result['carbs'] ?>g</div>
                        <div class="macro-label">Carbohidratos</div>
                    </div>
                    <div class="macro-item">
                        <div class="macro-value"><?= $result['fat'] ?>g</div>
                        <div class="macro-label">Grasas</div>
                    </div>
                    <div class="macro-item">
                        <div class="macro-value"><?= $result['target'] ?></div>
                        <div class="macro-label">Kcal totales</div>
                    </div>
                </div>
            </div>

            <!-- Generated Plan -->
            <?php if ($plan): ?>
                <div class="meal-plan animate-in">
                    <?php if ($plan['tipo'] === 'completo'): ?>
                        <h2><i class="fas fa-calendar-day" style="color:var(--accent)"></i> Tu plan completo del día</h2>
                        <?php
                        $categoryLabels = ['desayuno' => '🌅 Desayuno', 'comida' => '🍽️ Comida', 'cena' => '🌙 Cena', 'snack' => '🍎 Snack'];
                        $totalCal = 0;
                        foreach ($plan['recetas'] as $cat => $r):
                            $totalCal += $r['calories'];
                        ?>
                            <a href="<?= baseUrl() ?>/recipes/detail.php?id=<?= $r['id'] ?>" class="meal-card" style="text-decoration:none;color:inherit">
                                <div class="meal-card-image">
                                    <img src="<?= sanitize($r['image_url']) ?>" alt="<?= sanitize($r['title']) ?>" loading="lazy">
                                </div>
                                <div class="meal-card-body">
                                    <div class="meal-type"><?= $categoryLabels[$cat] ?? $cat ?></div>
                                    <h4><?= sanitize($r['title']) ?></h4>
                                    <p><?= sanitize($r['description']) ?></p>
                                </div>
                                <div class="meal-card-cal">
                                    <?= $r['calories'] ?>
                                    <small>kcal</small>
                                </div>
                            </a>
                        <?php endforeach; ?>

                        <div class="results-card" style="margin-top:16px;text-align:center">
                            <strong style="font-size:1.1rem">Total del plan: <span style="color:var(--accent-light)"><?= $totalCal ?> kcal</span></strong>
                            <span style="color:var(--text-muted);font-size:0.85rem"> / <?= $result['target'] ?> kcal objetivo</span>
                        </div>

                    <?php elseif ($plan['tipo'] === 'receta' && !empty($plan['recetas'])): ?>
                        <h2><i class="fas fa-utensils" style="color:var(--accent)"></i> Tu receta recomendada</h2>
                        <?php $r = $plan['recetas'][0]; ?>
                        <a href="<?= baseUrl() ?>/recipes/detail.php?id=<?= $r['id'] ?>" class="meal-card" style="text-decoration:none;color:inherit">
                            <div class="meal-card-image">
                                <img src="<?= sanitize($r['image_url']) ?>" alt="<?= sanitize($r['title']) ?>" loading="lazy">
                            </div>
                            <div class="meal-card-body">
                                <div class="meal-type"><?= sanitize($r['category']) ?></div>
                                <h4><?= sanitize($r['title']) ?></h4>
                                <p><?= sanitize($r['description']) ?></p>
                            </div>
                            <div class="meal-card-cal">
                                <?= $r['calories'] ?>
                                <small>kcal</small>
                            </div>
                        </a>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No se encontraron recetas</h3>
                            <p>No hay recetas que coincidan con tus filtros. Prueba con menos restricciones.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-3 no-print" style="display:flex;gap:12px;justify-content:center">
                <button onclick="printPlan()" class="btn btn-primary btn-lg">
                    <i class="fas fa-print"></i> Imprimir plan
                </button>
                <a href="index.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-redo"></i> Nuevo cálculo
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
