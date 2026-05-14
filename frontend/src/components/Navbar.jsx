// ============================================
// NutriFit — Navbar Component
// Ported from includes/header.php
// ============================================

import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Navbar() {
    const { user, isLoggedIn, isAdmin, logout } = useAuth();
    const [menuOpen, setMenuOpen] = useState(false);
    const location = useLocation();

    const toggleMenu = () => setMenuOpen(!menuOpen);
    const closeMenu = () => setMenuOpen(false);

    return (
        <nav className="navbar" id="navbar">
            <div className="nav-container">
                <Link to="/" className="nav-logo" onClick={closeMenu}>
                    <i className="fas fa-leaf"></i> Nutri<span>Fit</span>
                </Link>
                <button
                    className={`nav-toggle ${menuOpen ? 'active' : ''}`}
                    id="navToggle"
                    aria-label="Menú"
                    onClick={toggleMenu}
                >
                    <span></span><span></span><span></span>
                </button>
                <ul className={`nav-menu ${menuOpen ? 'active' : ''}`} id="navMenu">
                    <li><Link to="/" onClick={closeMenu}><i className="fas fa-home"></i> Inicio</Link></li>
                    <li><Link to="/recipes" onClick={closeMenu}><i className="fas fa-utensils"></i> Recetas</Link></li>
                    <li><Link to="/calculator" onClick={closeMenu}><i className="fas fa-calculator"></i> Calculadora</Link></li>
                    {isAdmin() && (
                        <li><Link to="/admin" onClick={closeMenu}><i className="fas fa-cogs"></i> Admin</Link></li>
                    )}
                    <li className="nav-auth">
                        {isLoggedIn() ? (
                            <>
                                <span className="nav-user"><i className="fas fa-user-circle"></i> {user.username}</span>
                                <button onClick={() => { logout(); closeMenu(); }} className="btn btn-sm btn-outline">Cerrar sesión</button>
                            </>
                        ) : (
                            <>
                                <Link to="/login" className="btn btn-sm btn-outline" onClick={closeMenu}>Entrar</Link>
                                <Link to="/register" className="btn btn-sm btn-primary" onClick={closeMenu}>Registro</Link>
                            </>
                        )}
                    </li>
                </ul>
            </div>
        </nav>
    );
}
