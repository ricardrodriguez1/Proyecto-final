<?php
// ============================================
// NutriFit — Helper Functions
// ============================================

session_start();
require_once __DIR__ . '/../config/database.php';

// --- Auth helpers ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Debes iniciar sesión para acceder.');
        redirect('/auth/login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        setFlash('error', 'No tienes permisos para acceder.');
        redirect('/index.php');
    }
}

// --- Utility helpers ---
function redirect($path) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    // Navigate relative to web root
    header("Location: " . $path);
    exit;
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// --- Base URL helper ---
function baseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Detect the web root dynamically
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Walk up until we find the web root (contains index.php)
    $webRoot = $scriptDir;
    while ($webRoot !== '/' && $webRoot !== '\\' && !empty($webRoot)) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $webRoot . '/index.php') &&
            file_exists($_SERVER['DOCUMENT_ROOT'] . $webRoot . '/config/database.php')) {
            break;
        }
        $webRoot = dirname($webRoot);
    }
    return $protocol . '://' . $host . $webRoot;
}

// --- Recipe helpers ---
function getRecipesByFilters($filters = []) {
    $db = getDB();
    $sql = "SELECT DISTINCT r.* FROM recipes r";
    $joins = [];
    $where = [];
    $params = [];

    if (!empty($filters['tags_exclude'])) {
        // Exclude recipes that have any of the excluded tags
        $excludePlaceholders = [];
        foreach ($filters['tags_exclude'] as $i => $tag) {
            $key = ":extag{$i}";
            $excludePlaceholders[] = $key;
            $params[$key] = $tag;
        }
        $where[] = "r.id NOT IN (SELECT recipe_id FROM recipe_tags WHERE tag IN (" . implode(',', $excludePlaceholders) . "))";
    }

    if (!empty($filters['diet_type'])) {
        if ($filters['diet_type'] === 'vegano') {
            $where[] = "r.diet_type = 'vegano'";
        } elseif ($filters['diet_type'] === 'vegetariano') {
            $where[] = "(r.diet_type = 'vegano' OR r.diet_type = 'vegetariano')";
        }
        // omnivoro = no filter needed
    }

    if (!empty($filters['category'])) {
        $where[] = "r.category = :category";
        $params[':category'] = $filters['category'];
    }

    if (!empty($filters['difficulty'])) {
        $where[] = "r.difficulty = :difficulty";
        $params[':difficulty'] = $filters['difficulty'];
    }

    if (!empty($filters['max_calories'])) {
        $where[] = "r.calories <= :max_cal";
        $params[':max_cal'] = $filters['max_calories'];
    }

    if (!empty($filters['search'])) {
        $where[] = "(r.title LIKE :search OR r.description LIKE :search2)";
        $params[':search'] = '%' . $filters['search'] . '%';
        $params[':search2'] = '%' . $filters['search'] . '%';
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY r.title ASC";

    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . intval($filters['limit']);
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getRecipeById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM recipes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function getRecipeIngredients($recipeId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM recipe_ingredients WHERE recipe_id = :id ORDER BY id");
    $stmt->execute([':id' => $recipeId]);
    return $stmt->fetchAll();
}

function getRecipeSteps($recipeId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM recipe_steps WHERE recipe_id = :id ORDER BY step_number");
    $stmt->execute([':id' => $recipeId]);
    return $stmt->fetchAll();
}

function getRecipeTags($recipeId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT tag FROM recipe_tags WHERE recipe_id = :id");
    $stmt->execute([':id' => $recipeId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// --- Metabolism calculator ---
function calculateBMR($gender, $weight, $height, $age) {
    // Mifflin-St Jeor Equation
    if ($gender === 'male') {
        return (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
    } else {
        return (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
    }
}

function calculateTDEE($bmr, $activityLevel) {
    $multipliers = [
        'sedentario'   => 1.2,
        'ligero'       => 1.375,
        'moderado'     => 1.55,
        'activo'       => 1.725,
        'muy_activo'   => 1.9,
    ];
    $mult = $multipliers[$activityLevel] ?? 1.2;
    return round($bmr * $mult);
}

function adjustCaloriesByObjective($tdee, $objective) {
    switch ($objective) {
        case 'volumen':      return round($tdee * 1.15);
        case 'definicion':   return round($tdee * 0.85);
        case 'mantenimiento':
        default:             return $tdee;
    }
}

// --- Meal plan generator ---
function generateMealPlan($targetCalories, $dietType, $allergies = [], $planType = 'completo') {
    $excludeTags = [];
    if (in_array('gluten', $allergies))       $excludeTags[] = 'contiene-gluten';
    if (in_array('lactosa', $allergies))       $excludeTags[] = 'contiene-lactosa';
    if (in_array('frutos_secos', $allergies))  $excludeTags[] = 'contiene-frutos-secos';
    if (in_array('mariscos', $allergies))      $excludeTags[] = 'contiene-mariscos';

    $filters = [
        'diet_type'    => $dietType,
        'tags_exclude' => $excludeTags,
    ];

    if ($planType === 'receta') {
        // Single recipe close to 1/3 of daily target
        $targetPerMeal = round($targetCalories / 3);
        $allRecipes = getRecipesByFilters($filters);
        // Find closest match
        usort($allRecipes, function($a, $b) use ($targetPerMeal) {
            return abs($a['calories'] - $targetPerMeal) - abs($b['calories'] - $targetPerMeal);
        });
        return ['tipo' => 'receta', 'recetas' => [array_shift($allRecipes)]];
    }

    // Complete plan: breakfast ~25%, lunch ~35%, dinner ~30%, snack ~10%
    $distribution = [
        'desayuno' => 0.25,
        'comida'   => 0.35,
        'cena'     => 0.30,
        'snack'    => 0.10,
    ];

    $plan = [];
    foreach ($distribution as $category => $pct) {
        $target = round($targetCalories * $pct);
        $catFilters = array_merge($filters, ['category' => $category]);
        $recipes = getRecipesByFilters($catFilters);

        if (!empty($recipes)) {
            usort($recipes, function($a, $b) use ($target) {
                return abs($a['calories'] - $target) - abs($b['calories'] - $target);
            });
            $plan[$category] = $recipes[0];
        }
    }

    return ['tipo' => 'completo', 'objetivo_kcal' => $targetCalories, 'recetas' => $plan];
}
