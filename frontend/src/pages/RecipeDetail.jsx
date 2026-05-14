// ============================================
// NutriFit — Recipe Detail Page
// Ported from recipes/detail.php
// ============================================

import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { recipesAPI } from '../services/api';

export default function RecipeDetail() {
    const { id } = useParams();
    const [recipe, setRecipe] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        recipesAPI.detail(id)
            .then(data => setRecipe(data.data))
            .catch(err => console.error('Error loading recipe:', err))
            .finally(() => setLoading(false));
    }, [id]);

    if (loading) return <div className="main-content"><div className="loading"></div></div>;

    if (!recipe) {
        return (
            <div className="main-content">
                <div className="container">
                    <div className="empty-state">
                        <i className="fas fa-exclamation-triangle"></i>
                        <h3>Receta no encontrada</h3>
                        <p>La receta que buscas no existe o ha sido eliminada.</p>
                        <Link to="/recipes" className="btn btn-outline mt-2">Volver a recetas</Link>
                    </div>
                </div>
            </div>
        );
    }

    // Calculate nutrition bar percentages (based on a 2000 kcal reference)
    const calPct = Math.min(100, Math.round((recipe.calories / 2000) * 100));
    const protPct = Math.min(100, Math.round((recipe.protein / 50) * 100));
    const carbPct = Math.min(100, Math.round((recipe.carbs / 300) * 100));
    const fatPct = Math.min(100, Math.round((recipe.fat / 65) * 100));

    return (
        <div className="recipe-detail">
            <div className="container">
                {/* Header Image */}
                <div className="recipe-header-image animate-in">
                    <img src={recipe.image_url} alt={recipe.title} />
                    <div className="overlay">
                        <div className="recipe-tags" style={{ marginBottom: '10px' }}>
                            <span className="tag">{recipe.category}</span>
                            <span className="tag">{recipe.diet_type}</span>
                            {recipe.tags && recipe.tags.map((tag, i) => (
                                <span key={i} className="tag">{tag}</span>
                            ))}
                        </div>
                        <h1>{recipe.title}</h1>
                    </div>
                </div>

                {/* Meta Info */}
                <div className="recipe-meta" style={{ fontSize: '0.95rem', gap: '24px', marginBottom: '8px' }}>
                    <span><i className="fas fa-clock"></i> Prep: {recipe.prep_time} min</span>
                    <span><i className="fas fa-fire-alt"></i> Cocción: {recipe.cook_time} min</span>
                    <span><i className="fas fa-users"></i> {recipe.servings} raciones</span>
                    <span><i className="fas fa-signal"></i> {recipe.difficulty}</span>
                </div>
                <p style={{ color: 'var(--text-secondary)', fontSize: '0.95rem', lineHeight: '1.7', marginBottom: '16px' }}>
                    {recipe.description}
                </p>

                {/* Two-column layout */}
                <div className="recipe-info-grid">
                    <div>
                        {/* Ingredients */}
                        <div className="ingredients-list animate-in">
                            <h3><i className="fas fa-carrot" style={{ color: 'var(--accent)' }}></i> Ingredientes</h3>
                            {recipe.ingredients && recipe.ingredients.map((ing, i) => (
                                <div key={i} className="ingredient-item">
                                    <span className="qty">{ing.quantity} {ing.unit}</span>
                                    <span>{ing.ingredient_name}</span>
                                </div>
                            ))}
                        </div>

                        {/* Steps */}
                        <div className="steps-list animate-in">
                            <h3><i className="fas fa-list-ol" style={{ color: 'var(--accent)' }}></i> Preparación</h3>
                            {recipe.steps && recipe.steps.map((step, i) => (
                                <div key={i} className="step-item">
                                    <div className="step-number">{step.step_number}</div>
                                    <p>{step.instruction}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Nutrition Sidebar */}
                    <div>
                        <div className="nutrition-card animate-in">
                            <h3><i className="fas fa-chart-pie" style={{ color: 'var(--accent)' }}></i> Información Nutricional</h3>
                            <p style={{ fontSize: '0.78rem', color: 'var(--text-muted)', marginBottom: '16px' }}>Por ración</p>

                            <div className="nutrition-item">
                                <span className="label">Calorías</span>
                                <span className="value" style={{ color: 'var(--accent-light)' }}>{recipe.calories} kcal</span>
                            </div>
                            <div className="nutrition-bar"><div className="nutrition-bar-fill" style={{ width: `${calPct}%` }}></div></div>

                            <div className="nutrition-item" style={{ marginTop: '16px' }}>
                                <span className="label">Proteínas</span>
                                <span className="value">{recipe.protein}g</span>
                            </div>
                            <div className="nutrition-bar"><div className="nutrition-bar-fill" style={{ width: `${protPct}%`, background: 'linear-gradient(90deg,#3b82f6,#60a5fa)' }}></div></div>

                            <div className="nutrition-item" style={{ marginTop: '16px' }}>
                                <span className="label">Carbohidratos</span>
                                <span className="value">{recipe.carbs}g</span>
                            </div>
                            <div className="nutrition-bar"><div className="nutrition-bar-fill" style={{ width: `${carbPct}%`, background: 'linear-gradient(90deg,#f59e0b,#fbbf24)' }}></div></div>

                            <div className="nutrition-item" style={{ marginTop: '16px' }}>
                                <span className="label">Grasas</span>
                                <span className="value">{recipe.fat}g</span>
                            </div>
                            <div className="nutrition-bar"><div className="nutrition-bar-fill" style={{ width: `${fatPct}%`, background: 'linear-gradient(90deg,#ef4444,#f87171)' }}></div></div>

                            <div className="nutrition-item" style={{ marginTop: '16px' }}>
                                <span className="label">Fibra</span>
                                <span className="value">{recipe.fiber}g</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="text-center mt-3 no-print">
                    <Link to="/recipes" className="btn btn-outline">
                        <i className="fas fa-arrow-left"></i> Volver a recetas
                    </Link>
                </div>
            </div>
        </div>
    );
}
