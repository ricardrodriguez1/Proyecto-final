// ============================================
// NutriFit — Calculator Helper Functions
// Ported from PHP includes/functions.php
// ============================================

// Mifflin-St Jeor Equation
function calculateBMR(gender, weight, height, age) {
    if (gender === 'male') {
        return (10 * weight) + (6.25 * height) - (5 * age) + 5;
    } else {
        return (10 * weight) + (6.25 * height) - (5 * age) - 161;
    }
}

function calculateTDEE(bmr, activityLevel) {
    const multipliers = {
        sedentario: 1.2,
        ligero: 1.375,
        moderado: 1.55,
        activo: 1.725,
        muy_activo: 1.9,
    };
    const mult = multipliers[activityLevel] || 1.2;
    return Math.round(bmr * mult);
}

function adjustCaloriesByObjective(tdee, objective) {
    switch (objective) {
        case 'volumen':      return Math.round(tdee * 1.15);
        case 'definicion':   return Math.round(tdee * 0.85);
        case 'mantenimiento':
        default:             return tdee;
    }
}

module.exports = { calculateBMR, calculateTDEE, adjustCaloriesByObjective };
