// ============================================
// NutriFit — Recipes Catalog Page
// Ported from recipes/index.php
// ============================================

import { useState, useEffect } from 'react';
import { recipesAPI } from '../services/api';
import RecipeCard from '../components/RecipeCard';

export default function Recipes() {
    const [recipes, setRecipes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        search: '',
        category: '',
        diet_type: '',
        difficulty: '',
    });

    const fetchRecipes = (params) => {
        setLoading(true);
        recipesAPI.list(params)
            .then(data => setRecipes(data.data || []))
            .catch(err => console.error('Error loading recipes:', err))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        fetchRecipes(filters);
    }, []);

    const handleFilterChange = (e) => {
        setFilters(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        fetchRecipes(filters);
    };

    const clearFilters = () => {
        const cleared = { search: '', category: '', diet_type: '', difficulty: '' };
        setFilters(cleared);
        fetchRecipes(cleared);
    };

    return (
        <div className="catalog-page">
            <div className="container">
                <div className="page-header">
                    <h1><i className="fas fa-utensils" style={{ color: 'var(--accent)' }}></i> Nuestras Recetas</h1>
                    <p>Explora nuestra colección de recetas saludables y deliciosas</p>
                </div>

                <div className="catalog-layout">
                    {/* Filter Sidebar */}
                    <aside className="filter-sidebar no-print">
                        <h3><i className="fas fa-filter"></i> Filtros</h3>
                        <form onSubmit={handleSubmit}>
                            <div className="filter-group">
                                <label>Buscar</label>
                                <input
                                    type="text"
                                    name="search"
                                    value={filters.search}
                                    onChange={handleFilterChange}
                                    placeholder="Buscar receta..."
                                />
                            </div>
                            <div className="filter-group">
                                <label>Categoría</label>
                                <select name="category" value={filters.category} onChange={handleFilterChange}>
                                    <option value="">Todas</option>
                                    <option value="desayuno">🌅 Desayuno</option>
                                    <option value="comida">🍽️ Comida</option>
                                    <option value="cena">🌙 Cena</option>
                                    <option value="snack">🍎 Snack</option>
                                </select>
                            </div>
                            <div className="filter-group">
                                <label>Tipo de dieta</label>
                                <select name="diet_type" value={filters.diet_type} onChange={handleFilterChange}>
                                    <option value="">Todas</option>
                                    <option value="omnivoro">🥩 Omnívoro</option>
                                    <option value="vegetariano">🥚 Vegetariano</option>
                                    <option value="vegano">🌱 Vegano</option>
                                </select>
                            </div>
                            <div className="filter-group">
                                <label>Dificultad</label>
                                <select name="difficulty" value={filters.difficulty} onChange={handleFilterChange}>
                                    <option value="">Todas</option>
                                    <option value="facil">Fácil</option>
                                    <option value="media">Media</option>
                                    <option value="dificil">Difícil</option>
                                </select>
                            </div>
                            <button type="submit" className="btn btn-primary btn-block">
                                <i className="fas fa-search"></i> Filtrar
                            </button>
                            <button type="button" onClick={clearFilters} className="btn btn-outline btn-block mt-2" style={{ textAlign: 'center' }}>
                                Limpiar filtros
                            </button>
                        </form>
                    </aside>

                    {/* Recipe Grid */}
                    <div>
                        <p style={{ color: 'var(--text-muted)', marginBottom: '20px', fontSize: '0.9rem' }}>
                            <strong>{recipes.length}</strong> recetas encontradas
                        </p>

                        {loading ? (
                            <div className="loading"></div>
                        ) : recipes.length === 0 ? (
                            <div className="empty-state">
                                <i className="fas fa-search"></i>
                                <h3>No se encontraron recetas</h3>
                                <p>Prueba con otros filtros o términos de búsqueda.</p>
                            </div>
                        ) : (
                            <div className="recipes-grid">
                                {recipes.map(recipe => (
                                    <RecipeCard key={recipe.id} recipe={recipe} />
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
