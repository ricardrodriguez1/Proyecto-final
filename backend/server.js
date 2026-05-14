// ============================================
// NutriFit — Express Server Entry Point
// ============================================

require('dotenv').config();

const express = require('express');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3001;

// --- Middleware ---
app.use(cors());
app.use(express.json());

// --- Routes ---
app.use('/api/auth', require('./routes/auth'));
app.use('/api/recipes', require('./routes/recipes'));
app.use('/api/calculator', require('./routes/calculator'));
app.use('/api/admin', require('./routes/admin'));

// --- Health check ---
app.get('/api', (req, res) => {
    res.json({ message: 'NutriFit API is running 🍃', version: '1.0.0' });
});

// --- 404 handler ---
app.use((req, res) => {
    res.status(404).json({ error: 'Ruta no encontrada.' });
});

// --- Error handler ---
app.use((err, req, res, next) => {
    console.error('Server error:', err);
    res.status(500).json({ error: 'Error interno del servidor.' });
});

// --- Start server ---
app.listen(PORT, () => {
    console.log(`✅ NutriFit API running on http://localhost:${PORT}`);
    console.log(`📡 Endpoints: http://localhost:${PORT}/api`);
});
