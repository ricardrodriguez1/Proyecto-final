// ============================================
// NutriFit — Main App with React Router
// ============================================

import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import ProtectedRoute from './components/ProtectedRoute';

// Pages
import Home from './pages/Home';
import Recipes from './pages/Recipes';
import RecipeDetail from './pages/RecipeDetail';
import Calculator from './pages/Calculator';
import Login from './pages/Login';
import Register from './pages/Register';
import AdminDashboard from './pages/AdminDashboard';
import AdminRecipes from './pages/AdminRecipes';
import AdminRecipeForm from './pages/AdminRecipeForm';
import AdminUsers from './pages/AdminUsers';

import './index.css';

export default function App() {
    return (
        <BrowserRouter>
            <AuthProvider>
                <Navbar />
                <main className="main-content">
                    <Routes>
                        {/* Public routes */}
                        <Route path="/" element={<Home />} />
                        <Route path="/recipes" element={<Recipes />} />
                        <Route path="/recipes/:id" element={<RecipeDetail />} />
                        <Route path="/calculator" element={<Calculator />} />
                        <Route path="/login" element={<Login />} />
                        <Route path="/register" element={<Register />} />

                        {/* Admin routes (protected) */}
                        <Route path="/admin" element={
                            <ProtectedRoute admin>
                                <AdminDashboard />
                            </ProtectedRoute>
                        } />
                        <Route path="/admin/recipes" element={
                            <ProtectedRoute admin>
                                <AdminRecipes />
                            </ProtectedRoute>
                        } />
                        <Route path="/admin/recipes/new" element={
                            <ProtectedRoute admin>
                                <AdminRecipeForm />
                            </ProtectedRoute>
                        } />
                        <Route path="/admin/recipes/edit/:id" element={
                            <ProtectedRoute admin>
                                <AdminRecipeForm />
                            </ProtectedRoute>
                        } />
                        <Route path="/admin/users" element={
                            <ProtectedRoute admin>
                                <AdminUsers />
                            </ProtectedRoute>
                        } />
                    </Routes>
                </main>
                <Footer />
            </AuthProvider>
        </BrowserRouter>
    );
}
