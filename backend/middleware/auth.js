// ============================================
// NutriFit — JWT Auth Middleware
// ============================================

const jwt = require('jsonwebtoken');

// Verify JWT token — attaches req.user
function authRequired(req, res, next) {
    const header = req.headers.authorization;
    if (!header || !header.startsWith('Bearer ')) {
        return res.status(401).json({ error: 'Debes iniciar sesión para acceder.' });
    }

    const token = header.split(' ')[1];
    try {
        const decoded = jwt.verify(token, process.env.JWT_SECRET);
        req.user = decoded; // { id, username, role }
        next();
    } catch (err) {
        return res.status(401).json({ error: 'Token inválido o expirado.' });
    }
}

// Require admin role
function adminRequired(req, res, next) {
    if (!req.user || req.user.role !== 'admin') {
        return res.status(403).json({ error: 'No tienes permisos para acceder.' });
    }
    next();
}

module.exports = { authRequired, adminRequired };
