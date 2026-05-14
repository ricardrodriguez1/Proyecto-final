// ============================================
// NutriFit — Login Page
// Ported from auth/login.php
// ============================================

import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Login() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [formData, setFormData] = useState({ username: '', password: '' });

    const handleChange = (e) => {
        setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            await login(formData);
            navigate('/');
        } catch (err) {
            setError(err.message || 'Error al iniciar sesión.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="auth-page">
            <div className="auth-card animate-in">
                <h1><i className="fas fa-sign-in-alt" style={{ color: 'var(--accent)' }}></i> Entrar</h1>
                <p className="subtitle">Accede a tu cuenta para gestionar tu alimentación</p>

                {error && (
                    <div className="flash flash-error" style={{ margin: '0 0 20px', padding: '12px', borderRadius: 'var(--radius-sm)' }}>
                        <i className="fas fa-exclamation-circle"></i> {error}
                    </div>
                )}

                <form onSubmit={handleSubmit}>
                    <div className="form-group">
                        <label htmlFor="username"><i className="fas fa-user"></i> Usuario o email</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value={formData.username}
                            onChange={handleChange}
                            required
                            placeholder="Tu usuario o email"
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
                            placeholder="Tu contraseña"
                        />
                    </div>
                    <button type="submit" className="btn btn-primary btn-block btn-lg" disabled={loading}>
                        <i className={loading ? 'fas fa-spinner fa-spin' : 'fas fa-sign-in-alt'}></i>
                        {loading ? 'Entrando...' : 'Iniciar sesión'}
                    </button>
                </form>

                <div className="auth-footer">
                    ¿No tienes cuenta? <Link to="/register">Regístrate gratis</Link>
                </div>
            </div>
        </div>
    );
}
