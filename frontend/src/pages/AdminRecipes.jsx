// ============================================
// NutriFit — Admin Recipes Page
// Ported from admin/recipes.php
// ============================================

import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { adminAPI } from '../services/api';

export default function AdminRecipes() {
    const [recipes, setRecipes] = useState([]);
    const [loading, setLoading] = useState(true);

    const fetchRecipes = () => {
        setLoading(true);
        adminAPI.listRecipes()
            .then(data => setRecipes(data.data || []))
            .catch(err => console.error('Error loading recipes:', err))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        fetchRecipes();
    }, []);

    const handleDelete = async (id, title) => {
        if (!window.confirm(`¿Seguro que quieres eliminar "${title}"?`)) return;
        try {
            await adminAPI.deleteRecipe(id);
            fetchRecipes();
        } catch (err) {
            console.error('Error deleting recipe:', err);
        }
    };

    if (loading) return <div className="main-content"><div className="loading"></div></div>;

    return (
        <div className="admin-page">
            <div className="container">
                <div className="admin-header">
                    <h1><i className="fas fa-utensils" style={{ color: 'var(--accent)' }}></i> Gestión de Recetas</h1>
                    <Link to="/admin/recipes/new" className="btn btn-primary"><i className="fas fa-plus"></i> Nueva receta</Link>
                </div>

                <div className="admin-table-wrapper">
                    <table className="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Dieta</th>
                                <th>Kcal</th>
                                <th>Dificultad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recipes.map(r => (
                                <tr key={r.id}>
                                    <td>{r.id}</td>
                                    <td><strong>{r.title}</strong></td>
                                    <td>{r.category}</td>
                                    <td>{r.diet_type}</td>
                                    <td>{r.calories}</td>
                                    <td>{r.difficulty}</td>
                                    <td>
                                        <div className="actions">
                                            <Link to={`/admin/recipes/edit/${r.id}`} className="btn-edit">
                                                <i className="fas fa-edit"></i> Editar
                                            </Link>
                                            <button
                                                className="btn-delete"
                                                onClick={() => handleDelete(r.id, r.title)}
                                            >
                                                <i className="fas fa-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="text-center mt-3">
                    <Link to="/admin" className="btn btn-outline"><i className="fas fa-arrow-left"></i> Volver al panel</Link>
                </div>
            </div>
        </div>
    );
}
