// ============================================
// NutriFit — Register Page
// Ported from auth/register.php
// ============================================

import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Register() {
    const { register } = useAuth();
    const navigate = useNavigate();
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [formData, setFormData] = useState({
        username: '',
        email: '',
        password: '',
        confirm_password: '',
    });

    const handleChange = (e) => {
        setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            await register(formData);
            navigate('/login');
        } catch (err) {
            setError(err.message || 'Error al registrarse.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="auth-page">
            <div className="auth-card animate-in">
                <h1><i className="fas fa-user-plus" style={{ color: 'var(--accent)' }}></i> Registro</h1>
                <p className="subtitle">Crea tu cuenta y empieza a planificar tu alimentación</p>

                {error && (
                    <div className="flash flash-error" style={{ margin: '0 0 20px', padding: '12px', borderRadius: 'var(--radius-sm)' }}>
                        <i className="fas fa-exclamation-circle"></i> {error}
                    </div>
                )}

                <form onSubmit={handleSubmit}>
                    <div className="form-group">
                        <label htmlFor="username"><i className="fas fa-user"></i> Nombre de usuario</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value={formData.username}
                            onChange={handleChange}
                            required
                            placeholder="Tu nombre de usuario"
                        />
                    </div>
                    <div className="form-group">
                        <label htmlFor="email"><i className="fas fa-envelope"></i> Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            required
                            placeholder="tu@email.com"
                        />
                    </div>
                    <div className="form-group">
                        <label htmlFor="password"><i className="fas fa-lock"></i> Contraseña</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            value={formData.password}
                            onChange={handleChange}
                            required
                            placeholder="Mínimo 6 caracteres"
                        />
                    </div>
                    <div className="form-group">
                        <label htmlFor="confirm_password"><i className="fas fa-lock"></i> Confirmar contraseña</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            value={formData.confirm_password}
                            onChange={handleChange}
                            required
                            placeholder="Repite la contraseña"
                        />
                    </div>
                    <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={loading}>
                        <i className={loading ? 'fas fa-spinner fa-spin' : 'fas fa-user-plus'}></i>
                        {loading ? 'Creando...' : 'Crear cuenta'}
                    </button>
                </form>

                <div className="auth-footer">
                    ¿Ya tienes cuenta? <Link to="/login">Inicia sesión</Link>
                </div>
            </div>
        </div>
    );
}
