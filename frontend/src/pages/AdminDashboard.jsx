// ============================================
// NutriFit — Admin Dashboard Page
// Ported from admin/index.php
// ============================================

import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { adminAPI } from '../services/api';

export default function AdminDashboard() {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        adminAPI.stats()
            .then(data => setStats(data))
            .catch(err => console.error('Error loading stats:', err))
            .finally(() => setLoading(false));
    }, []);

    if (loading) return <div className="main-content"><div className="loading"></div></div>;

    return (
        <div className="admin-page">
            <div className="container">
                <div className="admin-header">
                    <h1><i className="fas fa-cogs" style={{ color: 'var(--accent)' }}></i> Panel de Administración</h1>
                </div>

                {/* Stats */}
                <div className="stat-cards">
                    <div className="stat-card animate-in">
                        <div className="stat-icon green"><i className="fas fa-users"></i></div>
                        <div>
                            <div className="stat-value">{stats?.totalUsers || 0}</div>
                            <div className="stat-label">Usuarios registrados</div>
                        </div>
                    </div>
                    <div className="stat-card animate-in">
                        <div className="stat-icon blue"><i className="fas fa-utensils"></i></div>
                        <div>
                            <div className="stat-value">{stats?.totalRecipes || 0}</div>
                            <div className="stat-label">Recetas totales</div>
                        </div>
                    </div>
                    <div className="stat-card animate-in">
                        <div className="stat-icon orange"><i className="fas fa-leaf"></i></div>
                        <div>
                            <div className="stat-value">{stats?.totalVegan || 0}</div>
                            <div className="stat-label">Recetas veganas</div>
                        </div>
                    </div>
                </div>

                {/* Quick Links */}
                <div className="features-grid" style={{ gridTemplateColumns: '1fr 1fr' }}>
                    <Link to="/admin/recipes" className="feature-card" style={{ textDecoration: 'none', color: 'inherit', textAlign: 'left' }}>
                        <div className="feature-icon"><i className="fas fa-utensils"></i></div>
                        <h3>Gestionar Recetas</h3>
                        <p>Crear, editar y eliminar recetas de la base de datos.</p>
                    </Link>
                    <Link to="/admin/users" className="feature-card" style={{ textDecoration: 'none', color: 'inherit', textAlign: 'left' }}>
                        <div className="feature-icon"><i className="fas fa-users-cog"></i></div>
                        <h3>Gestionar Usuarios</h3>
                        <p>Ver y eliminar cuentas de usuario.</p>
                    </Link>
                </div>
            </div>
        </div>
    );
}
