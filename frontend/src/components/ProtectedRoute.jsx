// ============================================
// NutriFit — Protected Route Component
// Replaces PHP requireLogin() / requireAdmin()
// ============================================

import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function ProtectedRoute({ children, admin = false }) {
    const { isLoggedIn, isAdmin, loading } = useAuth();

    if (loading) return null;

    if (!isLoggedIn()) {
        return <Navigate to="/login" replace />;
    }

    if (admin && !isAdmin()) {
        return <Navigate to="/" replace />;
    }

    return children;
}
