// ============================================
// NutriFit — Footer Component
// Ported from includes/footer.php
// ============================================

import { Link } from 'react-router-dom';

export default function Footer() {
    return (
        <footer className="footer">
            <div className="container">
                <div className="footer-grid">
                    <div className="footer-brand">
                        <Link to="/" className="footer-logo">
                            <i className="fas fa-leaf"></i> Nutri<span>Fit</span>
                        </Link>
                        <p>Tu plataforma inteligente de recetas y planificación alimentaria personalizada.</p>
                    </div>
                    <div className="footer-links">
                        <h4>Navegación</h4>
                        <ul>
                            <li><Link to="/">Inicio</Link></li>
                            <li><Link to="/recipes">Recetas</Link></li>
                            <li><Link to="/calculator">Calculadora</Link></li>
                        </ul>
                    </div>
                    <div className="footer-links">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="#">Política de privacidad</a></li>
                            <li><a href="#">Términos de uso</a></li>
                        </ul>
                    </div>
                </div>
                <div className="footer-bottom">
                    <p>&copy; {new Date().getFullYear()} NutriFit. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    );
}
