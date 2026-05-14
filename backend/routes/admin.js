// ============================================
// NutriFit — Admin Routes (Protected)
// GET    /api/admin/stats
// GET    /api/admin/recipes
// POST   /api/admin/recipes
// PUT    /api/admin/recipes/:id
// DELETE /api/admin/recipes/:id
// GET    /api/admin/users
// DELETE /api/admin/users/:id
// ============================================

const express = require('express');
const router = express.Router();
const db = require('../config/database');
const { authRequired, adminRequired } = require('../middleware/auth');

// All admin routes require auth + admin role
router.use(authRequired, adminRequired);

// --- DASHBOARD STATS ---
router.get('/stats', async (req, res) => {
    try {
        const [[{ totalUsers }]] = await db.execute('SELECT COUNT(*) as totalUsers FROM users');
        const [[{ totalRecipes }]] = await db.execute('SELECT COUNT(*) as totalRecipes FROM recipes');
        const [[{ totalVegan }]] = await db.execute("SELECT COUNT(*) as totalVegan FROM recipes WHERE diet_type = 'vegano'");

        res.json({ totalUsers, totalRecipes, totalVegan });
    } catch (err) {
        console.error('Stats error:', err);
        res.status(500).json({ error: 'Error al obtener estadísticas.' });
    }
});

// --- LIST ALL RECIPES (admin) ---
router.get('/recipes', async (req, res) => {
    try {
        const [recipes] = await db.execute('SELECT * FROM recipes ORDER BY id DESC');
        res.json({ data: recipes });
    } catch (err) {
        console.error('Admin recipes error:', err);
        res.status(500).json({ error: 'Error al obtener las recetas.' });
    }
});

// --- CREATE RECIPE ---
router.post('/recipes', async (req, res) => {
    const conn = await db.getConnection();
    try {
        await conn.beginTransaction();

        const {
            title, description = '', image_url = '',
            prep_time = 0, cook_time = 0, servings = 1,
            difficulty = 'facil', category = 'comida', diet_type = 'omnivoro',
            calories = 0, protein = 0, carbs = 0, fat = 0, fiber = 0,
            ingredients = [], steps = [], tags = [],
        } = req.body;

        const [result] = await conn.execute(
            `INSERT INTO recipes (title,description,image_url,prep_time,cook_time,servings,difficulty,category,diet_type,calories,protein,carbs,fat,fiber)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
            [title, description, image_url, prep_time, cook_time, servings, difficulty, category, diet_type, calories, protein, carbs, fat, fiber]
        );
        const recipeId = result.insertId;

        // Insert ingredients
        for (const ing of ingredients) {
            if (ing.name && ing.name.trim()) {
                await conn.execute(
                    'INSERT INTO recipe_ingredients (recipe_id, ingredient_name, quantity, unit) VALUES (?,?,?,?)',
                    [recipeId, ing.name.trim(), (ing.quantity || '').trim(), (ing.unit || '').trim()]
                );
            }
        }

        // Insert steps
        let stepNum = 1;
        for (const stepText of steps) {
            if (stepText && stepText.trim()) {
                await conn.execute(
                    'INSERT INTO recipe_steps (recipe_id, step_number, instruction) VALUES (?,?,?)',
                    [recipeId, stepNum++, stepText.trim()]
                );
            }
        }

        // Insert tags
        for (const tag of tags) {
            if (tag && tag.trim()) {
                await conn.execute(
                    'INSERT INTO recipe_tags (recipe_id, tag) VALUES (?,?)',
                    [recipeId, tag.trim()]
                );
            }
        }

        await conn.commit();
        res.status(201).json({ message: 'Receta creada correctamente.', id: recipeId });
    } catch (err) {
        await conn.rollback();
        console.error('Create recipe error:', err);
        res.status(500).json({ error: 'Error al crear la receta.' });
    } finally {
        conn.release();
    }
});

// --- UPDATE RECIPE ---
router.put('/recipes/:id', async (req, res) => {
    const conn = await db.getConnection();
    try {
        await conn.beginTransaction();
        const recipeId = parseInt(req.params.id);

        const {
            title, description = '', image_url = '',
            prep_time = 0, cook_time = 0, servings = 1,
            difficulty = 'facil', category = 'comida', diet_type = 'omnivoro',
            calories = 0, protein = 0, carbs = 0, fat = 0, fiber = 0,
            ingredients = [], steps = [], tags = [],
        } = req.body;

        await conn.execute(
            `UPDATE recipes SET title=?,description=?,image_url=?,prep_time=?,cook_time=?,servings=?,
             difficulty=?,category=?,diet_type=?,calories=?,protein=?,carbs=?,fat=?,fiber=? WHERE id=?`,
            [title, description, image_url, prep_time, cook_time, servings, difficulty, category, diet_type, calories, protein, carbs, fat, fiber, recipeId]
        );

        // Replace ingredients
        await conn.execute('DELETE FROM recipe_ingredients WHERE recipe_id = ?', [recipeId]);
        for (const ing of ingredients) {
            if (ing.name && ing.name.trim()) {
                await conn.execute(
                    'INSERT INTO recipe_ingredients (recipe_id, ingredient_name, quantity, unit) VALUES (?,?,?,?)',
                    [recipeId, ing.name.trim(), (ing.quantity || '').trim(), (ing.unit || '').trim()]
                );
            }
        }

        // Replace steps
        await conn.execute('DELETE FROM recipe_steps WHERE recipe_id = ?', [recipeId]);
        let stepNum = 1;
        for (const stepText of steps) {
            if (stepText && stepText.trim()) {
                await conn.execute(
                    'INSERT INTO recipe_steps (recipe_id, step_number, instruction) VALUES (?,?,?)',
                    [recipeId, stepNum++, stepText.trim()]
                );
            }
        }

        // Replace tags
        await conn.execute('DELETE FROM recipe_tags WHERE recipe_id = ?', [recipeId]);
        for (const tag of tags) {
            if (tag && tag.trim()) {
                await conn.execute(
                    'INSERT INTO recipe_tags (recipe_id, tag) VALUES (?,?)',
                    [recipeId, tag.trim()]
                );
            }
        }

        await conn.commit();
        res.json({ message: 'Receta actualizada correctamente.' });
    } catch (err) {
        await conn.rollback();
        console.error('Update recipe error:', err);
        res.status(500).json({ error: 'Error al actualizar la receta.' });
    } finally {
        conn.release();
    }
});

// --- DELETE RECIPE ---
router.delete('/recipes/:id', async (req, res) => {
    try {
        const id = parseInt(req.params.id);
        await db.execute('DELETE FROM recipes WHERE id = ?', [id]);
        res.json({ message: 'Receta eliminada correctamente.' });
    } catch (err) {
        console.error('Delete recipe error:', err);
        res.status(500).json({ error: 'Error al eliminar la receta.' });
    }
});

// --- LIST USERS ---
router.get('/users', async (req, res) => {
    try {
        const [users] = await db.execute('SELECT id, username, email, role, created_at FROM users ORDER BY id DESC');
        res.json({ data: users });
    } catch (err) {
        console.error('Users list error:', err);
        res.status(500).json({ error: 'Error al obtener los usuarios.' });
    }
});

// --- DELETE USER ---
router.delete('/users/:id', async (req, res) => {
    try {
        const id = parseInt(req.params.id);
        // Prevent self-deletion
        if (id === req.user.id) {
            return res.status(400).json({ error: 'No puedes eliminar tu propia cuenta.' });
        }
        await db.execute('DELETE FROM users WHERE id = ?', [id]);
        res.json({ message: 'Usuario eliminado correctamente.' });
    } catch (err) {
        console.error('Delete user error:', err);
        res.status(500).json({ error: 'Error al eliminar el usuario.' });
    }
});

module.exports = router;
