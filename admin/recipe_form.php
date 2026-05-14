<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAdmin();

$db = getDB();
$editing = false;
$recipe = [
    'title' => '', 'description' => '', 'image_url' => '',
    'prep_time' => '', 'cook_time' => '', 'servings' => 1,
    'difficulty' => 'facil', 'category' => 'comida', 'diet_type' => 'omnivoro',
    'calories' => '', 'protein' => '', 'carbs' => '', 'fat' => '', 'fiber' => ''
];
$ingredients = [];
$steps = [];
$tags = [];

// Load existing recipe for editing
if (!empty($_GET['id'])) {
    $editing = true;
    $recipe = getRecipeById(intval($_GET['id']));
    if (!$recipe) { header("Location: recipes.php"); exit; }
    $ingredients = getRecipeIngredients($recipe['id']);
    $steps = getRecipeSteps($recipe['id']);
    $tags = getRecipeTags($recipe['id']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        ':title' => trim($_POST['title']),
        ':description' => trim($_POST['description']),
        ':image_url' => trim($_POST['image_url']),
        ':prep_time' => intval($_POST['prep_time']),
        ':cook_time' => intval($_POST['cook_time']),
        ':servings' => intval($_POST['servings']),
        ':difficulty' => $_POST['difficulty'],
        ':category' => $_POST['category'],
        ':diet_type' => $_POST['diet_type'],
        ':calories' => intval($_POST['calories']),
        ':protein' => floatval($_POST['protein']),
        ':carbs' => floatval($_POST['carbs']),
        ':fat' => floatval($_POST['fat']),
        ':fiber' => floatval($_POST['fiber']),
    ];

    if ($editing) {
        $sql = "UPDATE recipes SET title=:title, description=:description, image_url=:image_url,
                prep_time=:prep_time, cook_time=:cook_time, servings=:servings, difficulty=:difficulty,
                category=:category, diet_type=:diet_type, calories=:calories, protein=:protein,
                carbs=:carbs, fat=:fat, fiber=:fiber WHERE id=:id";
        $data[':id'] = intval($_POST['recipe_id']);
        $db->prepare($sql)->execute($data);
        $recipeId = intval($_POST['recipe_id']);
    } else {
        $sql = "INSERT INTO recipes (title,description,image_url,prep_time,cook_time,servings,difficulty,category,diet_type,calories,protein,carbs,fat,fiber)
                VALUES (:title,:description,:image_url,:prep_time,:cook_time,:servings,:difficulty,:category,:diet_type,:calories,:protein,:carbs,:fat,:fiber)";
        $db->prepare($sql)->execute($data);
        $recipeId = $db->lastInsertId();
    }

    // Update ingredients
    $db->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$recipeId]);
    if (!empty($_POST['ingredients'])) {
        $stmt = $db->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_name, quantity, unit) VALUES (?,?,?,?)");
        foreach ($_POST['ingredients'] as $ing) {
            if (!empty(trim($ing['name']))) {
                $stmt->execute([$recipeId, trim($ing['name']), trim($ing['quantity'] ?? ''), trim($ing['unit'] ?? '')]);
            }
        }
    }

    // Update steps
    $db->prepare("DELETE FROM recipe_steps WHERE recipe_id = ?")->execute([$recipeId]);
    if (!empty($_POST['steps'])) {
        $stmt = $db->prepare("INSERT INTO recipe_steps (recipe_id, step_number, instruction) VALUES (?,?,?)");
        $stepNum = 1;
        foreach ($_POST['steps'] as $stepText) {
            if (!empty(trim($stepText))) {
                $stmt->execute([$recipeId, $stepNum++, trim($stepText)]);
            }
        }
    }

    // Update tags
    $db->prepare("DELETE FROM recipe_tags WHERE recipe_id = ?")->execute([$recipeId]);
    if (!empty($_POST['tags'])) {
        $stmt = $db->prepare("INSERT INTO recipe_tags (recipe_id, tag) VALUES (?,?)");
        foreach ($_POST['tags'] as $tag) {
            if (!empty(trim($tag))) {
                $stmt->execute([$recipeId, trim($tag)]);
            }
        }
    }

    setFlash('success', $editing ? 'Receta actualizada correctamente.' : 'Receta creada correctamente.');
    header("Location: recipes.php");
    exit;
}

$pageTitle = $editing ? 'Editar Receta' : 'Nueva Receta';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-page">
    <div class="container">
        <div class="admin-header">
            <h1><i class="fas fa-<?= $editing ? 'edit' : 'plus-circle' ?>" style="color:var(--accent)"></i> <?= $editing ? 'Editar Receta' : 'Nueva Receta' ?></h1>
            <a href="recipes.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>

        <form method="POST" class="admin-form">
            <?php if ($editing): ?>
                <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">
            <?php endif; ?>

            <h2>Información básica</h2>
            <div class="form-group">
                <label for="title">Título *</label>
                <input type="text" name="title" id="title" value="<?= sanitize($recipe['title']) ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea name="description" id="description" rows="3"><?= sanitize($recipe['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="image_url">URL de imagen</label>
                <input type="url" name="image_url" id="image_url" value="<?= sanitize($recipe['image_url']) ?>" placeholder="https://...">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="prep_time">Tiempo prep. (min)</label>
                    <input type="number" name="prep_time" id="prep_time" value="<?= $recipe['prep_time'] ?>" min="0">
                </div>
                <div class="form-group">
                    <label for="cook_time">Tiempo cocción (min)</label>
                    <input type="number" name="cook_time" id="cook_time" value="<?= $recipe['cook_time'] ?>" min="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="servings">Raciones</label>
                    <input type="number" name="servings" id="servings" value="<?= $recipe['servings'] ?>" min="1">
                </div>
                <div class="form-group">
                    <label for="difficulty">Dificultad</label>
                    <select name="difficulty" id="difficulty">
                        <option value="facil" <?= $recipe['difficulty'] === 'facil' ? 'selected' : '' ?>>Fácil</option>
                        <option value="media" <?= $recipe['difficulty'] === 'media' ? 'selected' : '' ?>>Media</option>
                        <option value="dificil" <?= $recipe['difficulty'] === 'dificil' ? 'selected' : '' ?>>Difícil</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="category">Categoría</label>
                    <select name="category" id="category">
                        <option value="desayuno" <?= $recipe['category'] === 'desayuno' ? 'selected' : '' ?>>Desayuno</option>
                        <option value="comida" <?= $recipe['category'] === 'comida' ? 'selected' : '' ?>>Comida</option>
                        <option value="cena" <?= $recipe['category'] === 'cena' ? 'selected' : '' ?>>Cena</option>
                        <option value="snack" <?= $recipe['category'] === 'snack' ? 'selected' : '' ?>>Snack</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="diet_type">Tipo de dieta</label>
                    <select name="diet_type" id="diet_type">
                        <option value="omnivoro" <?= $recipe['diet_type'] === 'omnivoro' ? 'selected' : '' ?>>Omnívoro</option>
                        <option value="vegetariano" <?= $recipe['diet_type'] === 'vegetariano' ? 'selected' : '' ?>>Vegetariano</option>
                        <option value="vegano" <?= $recipe['diet_type'] === 'vegano' ? 'selected' : '' ?>>Vegano</option>
                    </select>
                </div>
            </div>

            <h2>Información nutricional</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="calories">Calorías (kcal)</label>
                    <input type="number" name="calories" id="calories" value="<?= $recipe['calories'] ?>" min="0">
                </div>
                <div class="form-group">
                    <label for="protein">Proteínas (g)</label>
                    <input type="number" name="protein" id="protein" value="<?= $recipe['protein'] ?>" min="0" step="0.1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="carbs">Carbohidratos (g)</label>
                    <input type="number" name="carbs" id="carbs" value="<?= $recipe['carbs'] ?>" min="0" step="0.1">
                </div>
                <div class="form-group">
                    <label for="fat">Grasas (g)</label>
                    <input type="number" name="fat" id="fat" value="<?= $recipe['fat'] ?>" min="0" step="0.1">
                </div>
            </div>
            <div class="form-group" style="max-width:50%">
                <label for="fiber">Fibra (g)</label>
                <input type="number" name="fiber" id="fiber" value="<?= $recipe['fiber'] ?>" min="0" step="0.1">
            </div>

            <h2>Ingredientes</h2>
            <div class="dynamic-list">
                <div class="items-container">
                    <?php if (!empty($ingredients)):
                        foreach ($ingredients as $i => $ing): ?>
                        <div class="item-row">
                            <input type="text" name="ingredients[<?= $i ?>][name]" value="<?= sanitize($ing['ingredient_name']) ?>" placeholder="Ingrediente" required>
                            <input type="text" name="ingredients[<?= $i ?>][quantity]" value="<?= sanitize($ing['quantity']) ?>" placeholder="Cantidad">
                            <input type="text" name="ingredients[<?= $i ?>][unit]" value="<?= sanitize($ing['unit']) ?>" placeholder="Unidad">
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="item-row">
                            <input type="text" name="ingredients[0][name]" placeholder="Ingrediente" required>
                            <input type="text" name="ingredients[0][quantity]" placeholder="Cantidad">
                            <input type="text" name="ingredients[0][unit]" placeholder="Unidad">
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="add-item-btn add-ingredient-btn"><i class="fas fa-plus"></i> Añadir ingrediente</button>
            </div>

            <h2 style="margin-top:24px">Pasos de preparación</h2>
            <div class="dynamic-list">
                <div class="items-container">
                    <?php if (!empty($steps)):
                        foreach ($steps as $i => $step): ?>
                        <div class="item-row" style="grid-template-columns: 1fr 40px;">
                            <textarea name="steps[<?= $i ?>]" placeholder="Paso <?= $i + 1 ?>..." rows="2" required><?= sanitize($step['instruction']) ?></textarea>
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="item-row" style="grid-template-columns: 1fr 40px;">
                            <textarea name="steps[0]" placeholder="Paso 1..." rows="2" required></textarea>
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="add-item-btn add-step-btn"><i class="fas fa-plus"></i> Añadir paso</button>
            </div>

            <h2 style="margin-top:24px">Etiquetas</h2>
            <div class="form-group">
                <div class="checkbox-group">
                    <?php
                    $allTags = ['alto-proteina','bajo-calorias','sin-gluten','sin-lactosa','contiene-gluten','contiene-lactosa','contiene-frutos-secos','contiene-mariscos','rapido','antioxidante','keto'];
                    foreach ($allTags as $t): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="tags[]" value="<?= $t ?>" <?= in_array($t, $tags) ? 'checked' : '' ?>>
                            <?= $t ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> <?= $editing ? 'Guardar cambios' : 'Crear receta' ?>
                </button>
                <a href="recipes.php" class="btn btn-outline btn-lg">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
