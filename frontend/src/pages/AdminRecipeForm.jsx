// ============================================
// NutriFit — Admin Recipe Form Page
// Ported from admin/recipe_form.php
// ============================================

import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { adminAPI, recipesAPI } from '../services/api';

const ALL_TAGS = [
    'alto-proteina', 'bajo-calorias', 'sin-gluten', 'sin-lactosa',
    'contiene-gluten', 'contiene-lactosa', 'contiene-frutos-secos',
    'contiene-mariscos', 'rapido', 'antioxidante', 'keto',
];

export default function AdminRecipeForm() {
    const { id } = useParams();
    const navigate = useNavigate();
    const editing = !!id;
    const [loading, setLoading] = useState(editing);
    const [saving, setSaving] = useState(false);

    const [formData, setFormData] = useState({
        title: '', description: '', image_url: '',
        prep_time: '', cook_time: '', servings: 1,
        difficulty: 'facil', category: 'comida', diet_type: 'omnivoro',
        calories: '', protein: '', carbs: '', fat: '', fiber: '',
    });

    const [ingredients, setIngredients] = useState([{ name: '', quantity: '', unit: '' }]);
    const [steps, setSteps] = useState(['']);
    const [tags, setTags] = useState([]);

    useEffect(() => {
        if (editing) {
            recipesAPI.detail(id)
                .then(data => {
                    const r = data.data;
                    setFormData({
                        title: r.title || '', description: r.description || '', image_url: r.image_url || '',
                        prep_time: r.prep_time || '', cook_time: r.cook_time || '', servings: r.servings || 1,
                        difficulty: r.difficulty || 'facil', category: r.category || 'comida', diet_type: r.diet_type || 'omnivoro',
                        calories: r.calories || '', protein: r.protein || '', carbs: r.carbs || '', fat: r.fat || '', fiber: r.fiber || '',
                    });
                    if (r.ingredients && r.ingredients.length > 0) {
                        setIngredients(r.ingredients.map(ing => ({
                            name: ing.ingredient_name || '', quantity: ing.quantity || '', unit: ing.unit || ''
                        })));
                    }
                    if (r.steps && r.steps.length > 0) {
                        setSteps(r.steps.map(s => s.instruction || ''));
                    }
                    if (r.tags) setTags(r.tags);
                })
                .catch(err => console.error('Error loading recipe:', err))
                .finally(() => setLoading(false));
        }
    }, [id, editing]);

    const handleChange = (e) => {
        setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleIngredientChange = (index, field, value) => {
        setIngredients(prev => prev.map((ing, i) => i === index ? { ...ing, [field]: value } : ing));
    };

    const addIngredient = () => setIngredients(prev => [...prev, { name: '', quantity: '', unit: '' }]);
    const removeIngredient = (index) => setIngredients(prev => prev.filter((_, i) => i !== index));

    const handleStepChange = (index, value) => {
        setSteps(prev => prev.map((s, i) => i === index ? value : s));
    };

    const addStep = () => setSteps(prev => [...prev, '']);
    const removeStep = (index) => setSteps(prev => prev.filter((_, i) => i !== index));

    const handleTagChange = (tag, checked) => {
        setTags(prev => checked ? [...prev, tag] : prev.filter(t => t !== tag));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);

        const payload = {
            ...formData,
            prep_time: parseInt(formData.prep_time) || 0,
            cook_time: parseInt(formData.cook_time) || 0,
            servings: parseInt(formData.servings) || 1,
            calories: parseInt(formData.calories) || 0,
            protein: parseFloat(formData.protein) || 0,
            carbs: parseFloat(formData.carbs) || 0,
            fat: parseFloat(formData.fat) || 0,
            fiber: parseFloat(formData.fiber) || 0,
            ingredients: ingredients.filter(ing => ing.name.trim()),
            steps: steps.filter(s => s.trim()),
            tags: tags,
        };

        try {
            if (editing) {
                await adminAPI.updateRecipe(id, payload);
            } else {
                await adminAPI.createRecipe(payload);
            }
            navigate('/admin/recipes');
        } catch (err) {
            console.error('Error saving recipe:', err);
            alert(err.message || 'Error al guardar la receta.');
        } finally {
            setSaving(false);
        }
    };

    if (loading) return <div className="main-content"><div className="loading"></div></div>;

    return (
        <div className="admin-page">
            <div className="container">
                <div className="admin-header">
                    <h1>
                        <i className={`fas fa-${editing ? 'edit' : 'plus-circle'}`} style={{ color: 'var(--accent)' }}></i>
                        {' '}{editing ? 'Editar Receta' : 'Nueva Receta'}
                    </h1>
                    <Link to="/admin/recipes" className="btn btn-outline"><i className="fas fa-arrow-left"></i> Volver</Link>
                </div>

                <form onSubmit={handleSubmit} className="admin-form">
                    <h2>Información básica</h2>
                    <div className="form-group">
                        <label htmlFor="title">Título *</label>
                        <input type="text" name="title" id="title" value={formData.title} onChange={handleChange} required />
                    </div>
                    <div className="form-group">
                        <label htmlFor="description">Descripción</label>
                        <textarea name="description" id="description" rows="3" value={formData.description} onChange={handleChange}></textarea>
                    </div>
                    <div className="form-group">
                        <label htmlFor="image_url">URL de imagen</label>
                        <input type="url" name="image_url" id="image_url" value={formData.image_url} onChange={handleChange} placeholder="https://..." />
                    </div>
                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="prep_time">Tiempo prep. (min)</label>
                            <input type="number" name="prep_time" id="prep_time" value={formData.prep_time} onChange={handleChange} min="0" />
                        </div>
                        <div className="form-group">
                            <label htmlFor="cook_time">Tiempo cocción (min)</label>
                            <input type="number" name="cook_time" id="cook_time" value={formData.cook_time} onChange={handleChange} min="0" />
                        </div>
                    </div>
                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="servings">Raciones</label>
                            <input type="number" name="servings" id="servings" value={formData.servings} onChange={handleChange} min="1" />
                        </div>
                        <div className="form-group">
                            <label htmlFor="difficulty">Dificultad</label>
                            <select name="difficulty" id="difficulty" value={formData.difficulty} onChange={handleChange}>
                                <option value="facil">Fácil</option>
                                <option value="media">Media</option>
                                <option value="dificil">Difícil</option>
                            </select>
                        </div>
                    </div>
                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="category">Categoría</label>
                            <select name="category" id="category" value={formData.category} onChange={handleChange}>
                                <option value="desayuno">Desayuno</option>
                                <option value="comida">Comida</option>
                                <option value="cena">Cena</option>
                                <option value="snack">Snack</option>
                            </select>
                        </div>
                        <div className="form-group">
                            <label htmlFor="diet_type">Tipo de dieta</label>
                            <select name="diet_type" id="diet_type" value={formData.diet_type} onChange={handleChange}>
                                <option value="omnivoro">Omnívoro</option>
                                <option value="vegetariano">Vegetariano</option>
                                <option value="vegano">Vegano</option>
                            </select>
                        </div>
                    </div>

                    <h2>Información nutricional</h2>
                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="calories">Calorías (kcal)</label>
                            <input type="number" name="calories" id="calories" value={formData.calories} onChange={handleChange} min="0" />
                        </div>
                        <div className="form-group">
                            <label htmlFor="protein">Proteínas (g)</label>
                            <input type="number" name="protein" id="protein" value={formData.protein} onChange={handleChange} min="0" step="0.1" />
                        </div>
                    </div>
                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="carbs">Carbohidratos (g)</label>
                            <input type="number" name="carbs" id="carbs" value={formData.carbs} onChange={handleChange} min="0" step="0.1" />
                        </div>
                        <div className="form-group">
                            <label htmlFor="fat">Grasas (g)</label>
                            <input type="number" name="fat" id="fat" value={formData.fat} onChange={handleChange} min="0" step="0.1" />
                        </div>
                    </div>
                    <div className="form-group" style={{ maxWidth: '50%' }}>
                        <label htmlFor="fiber">Fibra (g)</label>
                        <input type="number" name="fiber" id="fiber" value={formData.fiber} onChange={handleChange} min="0" step="0.1" />
                    </div>

                    <h2>Ingredientes</h2>
                    <div className="dynamic-list">
                        <div className="items-container">
                            {ingredients.map((ing, i) => (
                                <div key={i} className="item-row">
                                    <input type="text" value={ing.name} onChange={(e) => handleIngredientChange(i, 'name', e.target.value)} placeholder="Ingrediente" required />
                                    <input type="text" value={ing.quantity} onChange={(e) => handleIngredientChange(i, 'quantity', e.target.value)} placeholder="Cantidad" />
                                    <input type="text" value={ing.unit} onChange={(e) => handleIngredientChange(i, 'unit', e.target.value)} placeholder="Unidad" />
                                    <button type="button" className="remove-btn" onClick={() => removeIngredient(i)}>
                                        <i className="fas fa-times"></i>
                                    </button>
                                </div>
                            ))}
                        </div>
                        <button type="button" className="add-item-btn" onClick={addIngredient}>
                            <i className="fas fa-plus"></i> Añadir ingrediente
                        </button>
                    </div>

                    <h2 style={{ marginTop: '24px' }}>Pasos de preparación</h2>
                    <div className="dynamic-list">
                        <div className="items-container">
                            {steps.map((stepText, i) => (
                                <div key={i} className="item-row" style={{ gridTemplateColumns: '1fr 40px' }}>
                                    <textarea
                                        value={stepText}
                                        onChange={(e) => handleStepChange(i, e.target.value)}
                                        placeholder={`Paso ${i + 1}...`}
                                        rows="2"
                                        required
                                    ></textarea>
                                    <button type="button" className="remove-btn" onClick={() => removeStep(i)}>
                                        <i className="fas fa-times"></i>
                                    </button>
                                </div>
                            ))}
                        </div>
                        <button type="button" className="add-item-btn" onClick={addStep}>
                            <i className="fas fa-plus"></i> Añadir paso
                        </button>
                    </div>

                    <h2 style={{ marginTop: '24px' }}>Etiquetas</h2>
                    <div className="form-group">
                        <div className="checkbox-group">
                            {ALL_TAGS.map(t => (
                                <label key={t} className="checkbox-item">
                                    <input
                                        type="checkbox"
                                        checked={tags.includes(t)}
                                        onChange={(e) => handleTagChange(t, e.target.checked)}
                                    />
                                    {t}
                                </label>
                            ))}
                        </div>
                    </div>

                    <div style={{ display: 'flex', gap: '12px', marginTop: '24px' }}>
                        <button type="submit" className="btn btn-primary btn-lg" disabled={saving}>
                            <i className={saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'}></i>
                            {' '}{saving ? 'Guardando...' : editing ? 'Guardar cambios' : 'Crear receta'}
                        </button>
                        <Link to="/admin/recipes" className="btn btn-outline btn-lg">Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    );
}
