// ============================================
// NutriFit — Auth Routes
// POST /api/auth/login
// POST /api/auth/register
// POST /api/auth/logout
// GET  /api/auth/me
// ============================================

const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const db = require('../config/database');
const { authRequired } = require('../middleware/auth');

// --- LOGIN ---
router.post('/login', async (req, res) => {
    try {
        const { username, password } = req.body;

        if (!username || !password) {
            return res.status(400).json({ error: 'Introduce tu usuario y contraseña.' });
        }

        const [rows] = await db.execute(
            'SELECT * FROM users WHERE username = ? OR email = ?',
            [username, username]
        );

        const user = rows[0];
        if (!user || !bcrypt.compareSync(password, user.password)) {
            return res.status(401).json({ error: 'Credenciales incorrectas. Inténtalo de nuevo.' });
        }

        const token = jwt.sign(
            { id: user.id, username: user.username, role: user.role },
            process.env.JWT_SECRET,
            { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
        );

        res.json({
            message: `¡Bienvenido, ${user.username}!`,
            token,
            user: { id: user.id, username: user.username, role: user.role },
        });
    } catch (err) {
        console.error('Login error:', err);
        res.status(500).json({ error: 'Error interno del servidor.' });
    }
});

// --- REGISTER ---
router.post('/register', async (req, res) => {
    try {
        const { username, email, password, confirm_password } = req.body;

        if (!username || !email || !password) {
            return res.status(400).json({ error: 'Todos los campos son obligatorios.' });
        }

        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return res.status(400).json({ error: 'El email no es válido.' });
        }

        if (password.length < 6) {
            return res.status(400).json({ error: 'La contraseña debe tener al menos 6 caracteres.' });
        }

        if (password !== confirm_password) {
            return res.status(400).json({ error: 'Las contraseñas no coinciden.' });
        }

        // Check if user or email already exists
        const [existing] = await db.execute(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            [username, email]
        );

        if (existing.length > 0) {
            return res.status(409).json({ error: 'El nombre de usuario o email ya están registrados.' });
        }

        const hashedPassword = bcrypt.hashSync(password, 10);
        await db.execute(
            "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')",
            [username, email, hashedPassword]
        );

        res.status(201).json({ message: '¡Registro exitoso! Ya puedes iniciar sesión.' });
    } catch (err) {
        console.error('Register error:', err);
        res.status(500).json({ error: 'Error interno del servidor.' });
    }
});

// --- LOGOUT (client-side only with JWT, but we can acknowledge it) ---
router.post('/logout', (req, res) => {
    res.json({ message: 'Sesión cerrada correctamente.' });
});

// --- GET CURRENT USER ---
router.get('/me', authRequired, (req, res) => {
    res.json({ user: req.user });
});

module.exports = router;
