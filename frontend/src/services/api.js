// ============================================
// NutriFit — API Service Layer
// ============================================

const API_BASE = '/api';

// --- Helper: make a request ---
async function request(endpoint, options = {}) {
    const token = localStorage.getItem('nutrifit_token');

    const config = {
        headers: {
            'Content-Type': 'application/json',
            ...(token && { Authorization: `Bearer ${token}` }),
            ...options.headers,
        },
        ...options,
    };

    const res = await fetch(`${API_BASE}${endpoint}`, config);
    const data = await res.json();

    if (!res.ok) {
        throw new Error(data.error || 'Error en la solicitud');
    }

    return data;
}

// --- Auth ---
export const authAPI = {
    login: (credentials) => request('/auth/login', { method: 'POST', body: JSON.stringify(credentials) }),
    register: (userData) => request('/auth/register', { method: 'POST', body: JSON.stringify(userData) }),
    logout: () => request('/auth/logout', { method: 'POST' }),
    me: () => request('/auth/me'),
};

// --- Recipes ---
export const recipesAPI = {
    list: (params = {}) => {
        const query = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                query.append(key, value);
            }
        });
        const qs = query.toString();
        return request(`/recipes${qs ? '?' + qs : ''}`);
    },
    detail: (id) => request(`/recipes/${id}`),
};

// --- Calculator ---
export const calculatorAPI = {
    calculate: (formData) => request('/calculator', { method: 'POST', body: JSON.stringify(formData) }),
};

// --- Admin ---
export const adminAPI = {
    stats: () => request('/admin/stats'),
    listRecipes: () => request('/admin/recipes'),
    createRecipe: (data) => request('/admin/recipes', { method: 'POST', body: JSON.stringify(data) }),
    updateRecipe: (id, data) => request(`/admin/recipes/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteRecipe: (id) => request(`/admin/recipes/${id}`, { method: 'DELETE' }),
    listUsers: () => request('/admin/users'),
    deleteUser: (id) => request(`/admin/users/${id}`, { method: 'DELETE' }),
};
