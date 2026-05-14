// ============================================
// NutriFit — Calculator Routes
// POST /api/calculator
// ============================================

const express = require('express');
const router = express.Router();
const db = require('../config/database');
const { calculateBMR, calculateTDEE, adjustCaloriesByObjective } = require('../helpers/calculator');

// --- CALCULATE & GENERATE MEAL PLAN ---
router.post('/', async (req, res) => {
    try {
        const {
            genero = 'male',
            peso = 70,
            altura = 170,
            edad = 25,
            actividad = 'moderado',
            objetivo = 'mantenimiento',
            diet_type = 'omnivoro',
            plan_type = 'completo',
            allergies = [],
        } = req.body;

        const bmr = calculateBMR(genero, parseFloat(peso), parseFloat(altura), parseInt(edad));
        const tdee = calculateTDEE(bmr, actividad);
        const targetCal = adjustCaloriesByObjective(tdee, objetivo);

        // Macro distribution based on objective
        let protPct, carbPct, fatPct;
        switch (objetivo) {
            case 'volumen':
                protPct = 0.25; carbPct = 0.50; fatPct = 0.25;
                break;
            case 'definicion':
                protPct = 0.35; carbPct = 0.40; fatPct = 0.25;
                break;
            default:
                protPct = 0.30; carbPct = 0.45; fatPct = 0.25;
        }

        const result = {
            bmr: Math.round(bmr),
            tdee,
            target: targetCal,
            protein: Math.round((targetCal * protPct) / 4),
            carbs: Math.round((targetCal * carbPct) / 4),
            fat: Math.round((targetCal * fatPct) / 9),
            objetivo,
        };

        // Generate meal plan
        const plan = await generateMealPlan(targetCal, diet_type, allergies, plan_type);

        res.json({ result, plan });
    } catch (err) {
        console.error('Calculator error:', err);
        res.status(500).json({ error: 'Error al calcular el plan nutricional.' });
    }
});

// --- MEAL PLAN GENERATOR (ported from PHP) ---
async function generateMealPlan(targetCalories, dietType, allergies = [], planType = 'completo') {
    const excludeTags = [];
    if (allergies.includes('gluten'))        excludeTags.push('contiene-gluten');
    if (allergies.includes('lactosa'))       excludeTags.push('contiene-lactosa');
    if (allergies.includes('frutos_secos'))  excludeTags.push('contiene-frutos-secos');
    if (allergies.includes('mariscos'))      excludeTags.push('contiene-mariscos');

    if (planType === 'receta') {
        // Single recipe close to 1/3 of daily target
        const targetPerMeal = Math.round(targetCalories / 3);
        const recipes = await getFilteredRecipes(dietType, excludeTags, null);

        if (recipes.length === 0) return { tipo: 'receta', recetas: [] };

        recipes.sort((a, b) => Math.abs(a.calories - targetPerMeal) - Math.abs(b.calories - targetPerMeal));
        return { tipo: 'receta', recetas: [recipes[0]] };
    }

    // Complete plan: breakfast ~25%, lunch ~35%, dinner ~30%, snack ~10%
    const distribution = {
        desayuno: 0.25,
        comida: 0.35,
        cena: 0.30,
        snack: 0.10,
    };

    const plan = {};
    for (const [category, pct] of Object.entries(distribution)) {
        const target = Math.round(targetCalories * pct);
        const recipes = await getFilteredRecipes(dietType, excludeTags, category);

        if (recipes.length > 0) {
            recipes.sort((a, b) => Math.abs(a.calories - target) - Math.abs(b.calories - target));
            plan[category] = recipes[0];
        }
    }

    return { tipo: 'completo', objetivo_kcal: targetCalories, recetas: plan };
}

async function getFilteredRecipes(dietType, excludeTags, category) {
    let sql = 'SELECT DISTINCT r.* FROM recipes r';
    const where = [];
    const params = [];

    if (excludeTags.length > 0) {
        const placeholders = excludeTags.map(() => '?').join(',');
        where.push(`r.id NOT IN (SELECT recipe_id FROM recipe_tags WHERE tag IN (${placeholders}))`);
        params.push(...excludeTags);
    }

    if (dietType === 'vegano') {
        where.push("r.diet_type = 'vegano'");
    } else if (dietType === 'vegetariano') {
        where.push("(r.diet_type = 'vegano' OR r.diet_type = 'vegetariano')");
    }

    if (category) {
        where.push('r.category = ?');
        params.push(category);
    }

    if (where.length > 0) {
        sql += ' WHERE ' + where.join(' AND ');
    }

    sql += ' ORDER BY r.title ASC';

    const [rows] = await db.execute(sql, params);
    return rows;
}

module.exports = router;
