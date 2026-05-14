// ============================================
// NutriFit — Recipes Routes
// GET /api/recipes          — list with filters
// GET /api/recipes/:id      — detail with ingredients, steps, tags
// ============================================

const express = require('express');
const router = express.Router();
const db = require('../config/database');

// --- LIST RECIPES (with filters) ---
router.get('/', async (req, res) => {
    try {
        let sql = 'SELECT DISTINCT r.* FROM recipes r';
        const where = [];
        const params = [];

        // Exclude tags (for allergy filtering)
        if (req.query.tags_exclude) {
            const tags = Array.isArray(req.query.tags_exclude)
                ? req.query.tags_exclude
                : [req.query.tags_exclude];
            const placeholders = tags.map(() => '?').join(',');
            where.push(`r.id NOT IN (SELECT recipe_id FROM recipe_tags WHERE tag IN (${placeholders}))`);
            params.push(...tags);
        }

        // Diet type filter
        if (req.query.diet_type) {
            if (req.query.diet_type === 'vegano') {
                where.push("r.diet_type = 'vegano'");
            } else if (req.query.diet_type === 'vegetariano') {
                where.push("(r.diet_type = 'vegano' OR r.diet_type = 'vegetariano')");
            }
            // omnivoro = no filter
        }

        // Category filter
        if (req.query.category) {
            where.push('r.category = ?');
            params.push(req.query.category);
        }

        // Difficulty filter
        if (req.query.difficulty) {
            where.push('r.difficulty = ?');
            params.push(req.query.difficulty);
        }

        // Max calories filter
        if (req.query.max_calories) {
            where.push('r.calories <= ?');
            params.push(parseInt(req.query.max_calories));
        }

        // Search filter
        if (req.query.search) {
            where.push('(r.title LIKE ? OR r.description LIKE ?)');
            params.push(`%${req.query.search}%`, `%${req.query.search}%`);
        }

        if (where.length > 0) {
            sql += ' WHERE ' + where.join(' AND ');
        }

        sql += ' ORDER BY r.title ASC';

        if (req.query.limit) {
            sql += ' LIMIT ?';
            params.push(parseInt(req.query.limit));
        }

        const [recipes] = await db.execute(sql, params);

        // Get tags for each recipe
        for (const recipe of recipes) {
            const [tags] = await db.execute(
                'SELECT tag FROM recipe_tags WHERE recipe_id = ?',
                [recipe.id]
            );
            recipe.tags = tags.map(t => t.tag);
        }

        res.json({ data: recipes });
    } catch (err) {
        console.error('Recipes list error:', err);
        res.status(500).json({ error: 'Error al obtener las recetas.' });
    }
});

// --- GET RECIPE DETAIL ---
router.get('/:id', async (req, res) => {
    try {
        const id = parseInt(req.params.id);
        if (!id) return res.status(400).json({ error: 'ID inválido.' });

        const [recipes] = await db.execute('SELECT * FROM recipes WHERE id = ?', [id]);
        if (recipes.length === 0) {
            return res.status(404).json({ error: 'Receta no encontrada.' });
        }

        const recipe = recipes[0];

        // Get ingredients
        const [ingredients] = await db.execute(
            'SELECT * FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id',
            [id]
        );

        // Get steps
        const [steps] = await db.execute(
            'SELECT * FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number',
            [id]
        );

        // Get tags
        const [tags] = await db.execute(
            'SELECT tag FROM recipe_tags WHERE recipe_id = ?',
            [id]
        );

        recipe.ingredients = ingredients;
        recipe.steps = steps;
        recipe.tags = tags.map(t => t.tag);

        res.json({ data: recipe });
    } catch (err) {
        console.error('Recipe detail error:', err);
        res.status(500).json({ error: 'Error al obtener la receta.' });
    }
});

module.exports = router;
