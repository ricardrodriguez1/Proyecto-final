// ============================================
// NutriFit — Admin Users Page
// Ported from admin/users.php
// ============================================

import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { adminAPI } from '../services/api';
import { useAuth } from '../context/AuthContext';

export default function AdminUsers() {
    const { user: currentUser } = useAuth();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);

    const fetchUsers = () => {
        setLoading(true);
        adminAPI.listUsers()
            .then(data => setUsers(data.data || []))
            .catch(err => console.error('Error loading users:', err))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        fetchUsers();
    }, []);

    const handleDelete = async (id, username) => {
        if (!window.confirm(`¿Seguro que quieres eliminar a "${username}"?`)) return;
        try {
            await adminAPI.deleteUser(id);
            fetchUsers();
        } catch (err) {
            console.error('Error deleting user:', err);
            alert(err.message || 'Error al eliminar el usuario.');
        }
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    };

    if (loading) return <div className="main-content"><div className="loading"></div></div>;

    return (
        <div className="admin-page">
            <div className="container">
                <div className="admin-header">
                    <h1><i className="fas fa-users" style={{ color: 'var(--accent)' }}></i> Gestión de Usuarios</h1>
                </div>

                <div className="admin-table-wrapper">
                    <table className="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.map(u => (
                                <tr key={u.id}>
                                    <td>{u.id}</td>
                                    <td><strong>{u.username}</strong></td>
                                    <td>{u.email}</td>
                                    <td>
                                        <span
                                            className="tag"
                                            style={u.role === 'admin' ? {
                                                background: 'rgba(245,158,11,0.15)',
                                                color: '#fbbf24',
                                                borderColor: 'rgba(245,158,11,0.2)'
                                            } : {}}
                                        >
                                            {u.role}
                                        </span>
                                    </td>
                                    <td>{formatDate(u.created_at)}</td>
                                    <td>
                                        <div className="actions">
                                            {currentUser && u.id !== currentUser.id ? (
                                                <button
                                                    className="btn-delete"
                                                    onClick={() => handleDelete(u.id, u.username)}
                                                >
                                                    <i className="fas fa-trash"></i> Eliminar
                                                </button>
                                            ) : (
                                                <span style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>Tu cuenta</span>
                                            )}
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
