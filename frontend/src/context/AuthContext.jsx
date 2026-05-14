// ============================================
// NutriFit — Auth Context (Global State)
// ============================================

import { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    // Check if there's a saved token on mount
    useEffect(() => {
        const token = localStorage.getItem('nutrifit_token');
        const savedUser = localStorage.getItem('nutrifit_user');
        if (token && savedUser) {
            try {
                setUser(JSON.parse(savedUser));
            } catch {
                localStorage.removeItem('nutrifit_token');
                localStorage.removeItem('nutrifit_user');
            }
        }
        setLoading(false);
    }, []);

    const login = async (credentials) => {
        const data = await authAPI.login(credentials);
        localStorage.setItem('nutrifit_token', data.token);
        localStorage.setItem('nutrifit_user', JSON.stringify(data.user));
        setUser(data.user);
        return data;
    };

    const register = async (userData) => {
        const data = await authAPI.register(userData);
        return data;
    };

    const logout = () => {
        localStorage.removeItem('nutrifit_token');
        localStorage.removeItem('nutrifit_user');
        setUser(null);
    };

    const isLoggedIn = () => !!user;
    const isAdmin = () => user?.role === 'admin';

    return (
        <AuthContext.Provider value={{ user, loading, login, register, logout, isLoggedIn, isAdmin }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
}
