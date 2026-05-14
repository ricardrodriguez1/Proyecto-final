// ============================================
// NutriFit — Home Page
// Ported from index.php
// ============================================

import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { recipesAPI } from '../services/api';
import RecipeCard from '../components/RecipeCard';

export default function Home() {
    const [recipes, setRecipes] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        recipesAPI.list({ limit: 6 })
            .then(data => setRecipes(data.data || []))
            .catch(err => console.error('Error loading recipes:', err))
            .finally(() => setLoading(false));
    }, []);

    return (
        <>
            {/* Hero Section */}
            <section className="hero">
                <div className="container">
                    <h1 className="animate-in">Cocina inteligente,<br />vida <span className="accent">saludable</span></h1>
                    <p className="animate-in">Descubre recetas personalizadas, calcula tus necesidades nutricionales y genera planes alimentarios adaptados a tus objetivos. Todo en un solo lugar.</p>
                    <div className="hero-buttons animate-in">
                        <Link to="/calculator" className="btn btn-primary btn-lg">
                            <i className="fas fa-calculator"></i> Calcula tu plan
                        </Link>
                        <Link to="/recipes" className="btn btn-outline btn-lg">
                            <i className="fas fa-utensils"></i> Explorar recetas
                        </Link>
                    </div>
                </div>
            </section>

            {/* Features Section */}
            <section className="features">
                <div className="container">
                    <div className="section-title">
                        <h2>¿Cómo funciona?</h2>
                        <p>Tres pasos para transformar tu alimentación</p>
                    </div>
                    <div className="features-grid">
                        <div className="feature-card">
                            <div className="feature-icon"><i className="fas fa-heartbeat"></i></div>
                            <h3>Calcula tu metabolismo</h3>
                            <p>Introduce tus datos básicos y descubre cuántas calorías necesitas al día según tu nivel de actividad.</p>
                        </div>
                        <div className="feature-card">
                            <div className="feature-icon"><i className="fas fa-bullseye"></i></div>
                            <h3>Elige tu objetivo</h3>
                            <p>¿Quieres definir, mantener o ganar masa? Ajustamos las calorías y macros a tu meta personal.</p>
                        </div>
                        <div className="feature-card">
                            <div className="feature-icon"><i className="fas fa-clipboard-list"></i></div>
                            <h3>Genera tu plan</h3>
                            <p>Recibe una receta o un plan completo (desayuno, comida, cena y snack) adaptado a tus preferencias y alergias.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Featured Recipes Section */}
            <section className="recipes-section">
                <div className="container">
                    <div className="section-title">
                        <h2>Recetas destacadas</h2>
                        <p>Descubre nuestra selección de recetas saludables y deliciosas</p>
                    </div>
                    {loading ? (
                        <div className="loading"></div>
                    ) : (
                        <div className="recipes-grid">
                            {recipes.map(recipe => (
                                <RecipeCard key={recipe.id} recipe={recipe} />
                            ))}
                        </div>
                    )}
                    <div className="text-center mt-3">
                        <Link to="/recipes" className="btn btn-outline btn-lg">
                            <i className="fas fa-arrow-right"></i> Ver todas las recetas
                        </Link>
                    </div>
                </div>
            </section>
        </>
    );
}
