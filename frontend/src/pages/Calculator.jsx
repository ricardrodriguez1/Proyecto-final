// ============================================
// NutriFit — Calculator Page
// Ported from calculator/index.php
// ============================================

import { useState } from 'react';
import { Link } from 'react-router-dom';
import { calculatorAPI } from '../services/api';

export default function Calculator() {
    const [step, setStep] = useState(1);
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState(null);
    const [plan, setPlan] = useState(null);

    const [formData, setFormData] = useState({
        genero: '',
        edad: '',
        peso: '',
        altura: '',
        actividad: '',
        objetivo: '',
        diet_type: 'omnivoro',
        allergies: [],
        plan_type: 'completo',
    });

    const handleChange = (e) => {
        setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleAllergyChange = (e) => {
        const { value, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            allergies: checked
                ? [...prev.allergies, value]
                : prev.allergies.filter(a => a !== value),
        }));
    };

    const nextStep = () => setStep(prev => prev + 1);
    const prevStep = () => setStep(prev => prev - 1);

    const handleSubmit = async () => {
        setLoading(true);
        try {
            const data = await calculatorAPI.calculate(formData);
            setResult(data.result);
            setPlan(data.plan);
        } catch (err) {
            console.error('Calculator error:', err);
        } finally {
            setLoading(false);
        }
    };

    const resetCalc = () => {
        setStep(1);
        setResult(null);
        setPlan(null);
        setFormData({
            genero: '', edad: '', peso: '', altura: '', actividad: '',
            objetivo: '', diet_type: 'omnivoro', allergies: [], plan_type: 'completo',
        });
    };

    const categoryLabels = {
        desayuno: '🌅 Desayuno',
        comida: '🍽️ Comida',
        cena: '🌙 Cena',
        snack: '🍎 Snack',
    };

    return (
        <div className="calculator-page">
            <div className="container">
                <div className="page-header">
                    <h1><i className="fas fa-calculator" style={{ color: 'var(--accent)' }}></i> Calculadora Nutricional</h1>
                    <p>Calcula tu metabolismo, elige tu objetivo y genera un plan personalizado</p>
                </div>

                {!result ? (
                    <div className="calc-container">
                        {/* Step Indicators */}
                        <div className="calc-steps no-print">
                            {['Datos', 'Objetivo', 'Preferencias', 'Plan'].map((label, i) => (
                                <div
                                    key={i}
                                    className={`calc-step-indicator ${step === i + 1 ? 'active' : ''} ${step > i + 1 ? 'completed' : ''}`}
                                >
                                    <span className="step-num">{step > i + 1 ? '✓' : i + 1}</span> {label}
                                </div>
                            ))}
                        </div>

                        {/* STEP 1: Personal Data */}
                        {step === 1 && (
                            <div className="calc-card animate-in">
                                <h2><i className="fas fa-heartbeat" style={{ color: 'var(--accent)' }}></i> Tus datos personales</h2>
                                <p style={{ color: 'var(--text-secondary)', marginBottom: '24px', fontSize: '0.9rem' }}>
                                    Introduce tus datos para calcular tu metabolismo basal (BMR) y gasto calórico diario (TDEE).
                                </p>

                                <div className="form-row">
                                    <div className="form-group">
                                        <label htmlFor="genero">Género</label>
                                        <select name="genero" id="genero" value={formData.genero} onChange={handleChange} required>
                                            <option value="">Seleccionar...</option>
                                            <option value="male">Hombre</option>
                                            <option value="female">Mujer</option>
                                        </select>
                                    </div>
                                    <div className="form-group">
                                        <label htmlFor="edad">Edad (años)</label>
                                        <input type="number" name="edad" id="edad" min="14" max="100" placeholder="25" value={formData.edad} onChange={handleChange} required />
                                    </div>
                                </div>
                                <div className="form-row">
                                    <div className="form-group">
                                        <label htmlFor="peso">Peso (kg)</label>
                                        <input type="number" name="peso" id="peso" min="30" max="250" step="0.1" placeholder="70" value={formData.peso} onChange={handleChange} required />
                                    </div>
                                    <div className="form-group">
                                        <label htmlFor="altura">Altura (cm)</label>
                                        <input type="number" name="altura" id="altura" min="100" max="250" placeholder="170" value={formData.altura} onChange={handleChange} required />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label htmlFor="actividad">Nivel de actividad física</label>
                                    <select name="actividad" id="actividad" value={formData.actividad} onChange={handleChange} required>
                                        <option value="">Seleccionar...</option>
                                        <option value="sedentario">Sedentario (poco o ningún ejercicio)</option>
                                        <option value="ligero">Ligero (1-3 días/semana)</option>
                                        <option value="moderado">Moderado (3-5 días/semana)</option>
                                        <option value="activo">Activo (6-7 días/semana)</option>
                                        <option value="muy_activo">Muy activo (atleta, trabajo físico)</option>
                                    </select>
                                </div>
                                <div style={{ textAlign: 'right' }}>
                                    <button type="button" className="btn btn-primary" onClick={nextStep} disabled={!formData.genero || !formData.edad || !formData.peso || !formData.altura || !formData.actividad}>
                                        <i className="fas fa-arrow-right"></i> Siguiente
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* STEP 2: Objective */}
                        {step === 2 && (
                            <div className="calc-card animate-in">
                                <h2><i className="fas fa-bullseye" style={{ color: 'var(--accent)' }}></i> Tu objetivo</h2>
                                <p style={{ color: 'var(--text-secondary)', marginBottom: '24px', fontSize: '0.9rem' }}>
                                    ¿Qué quieres conseguir con tu alimentación?
                                </p>

                                <div className="objective-cards">
                                    {[
                                        { value: 'volumen', icon: 'fas fa-dumbbell', title: 'Volumen', desc: 'Ganar masa muscular con superávit calórico (+15%)' },
                                        { value: 'mantenimiento', icon: 'fas fa-balance-scale', title: 'Mantenimiento', desc: 'Mantener tu peso actual con calorías equilibradas' },
                                        { value: 'definicion', icon: 'fas fa-fire-alt', title: 'Definición', desc: 'Perder grasa con déficit calórico controlado (-15%)' },
                                    ].map(obj => (
                                        <div
                                            key={obj.value}
                                            className={`objective-card ${formData.objetivo === obj.value ? 'selected' : ''}`}
                                            onClick={() => setFormData(prev => ({ ...prev, objetivo: obj.value }))}
                                        >
                                            <i className={obj.icon}></i>
                                            <h4>{obj.title}</h4>
                                            <p>{obj.desc}</p>
                                        </div>
                                    ))}
                                </div>

                                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                    <button type="button" className="btn btn-outline" onClick={prevStep}>
                                        <i className="fas fa-arrow-left"></i> Anterior
                                    </button>
                                    <button type="button" className="btn btn-primary" onClick={nextStep} disabled={!formData.objetivo}>
                                        <i className="fas fa-arrow-right"></i> Siguiente
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* STEP 3: Preferences */}
                        {step === 3 && (
                            <div className="calc-card animate-in">
                                <h2><i className="fas fa-sliders-h" style={{ color: 'var(--accent)' }}></i> Preferencias alimentarias</h2>
                                <p style={{ color: 'var(--text-secondary)', marginBottom: '24px', fontSize: '0.9rem' }}>
                                    Personaliza las recetas según tu dieta y posibles alergias.
                                </p>

                                <div className="form-group">
                                    <label>Tipo de dieta</label>
                                    <div className="checkbox-group">
                                        {[
                                            { value: 'omnivoro', label: '🥩 Omnívoro' },
                                            { value: 'vegetariano', label: '🥚 Vegetariano' },
                                            { value: 'vegano', label: '🌱 Vegano' },
                                        ].map(d => (
                                            <label key={d.value} className="checkbox-item">
                                                <input
                                                    type="radio"
                                                    name="diet_type"
                                                    value={d.value}
                                                    checked={formData.diet_type === d.value}
                                                    onChange={handleChange}
                                                /> {d.label}
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div className="form-group">
                                    <label>Alergias / Intolerancias</label>
                                    <div className="checkbox-group">
                                        {[
                                            { value: 'gluten', label: '🌾 Sin gluten' },
                                            { value: 'lactosa', label: '🥛 Sin lactosa' },
                                            { value: 'frutos_secos', label: '🥜 Sin frutos secos' },
                                            { value: 'mariscos', label: '🦐 Sin mariscos' },
                                        ].map(a => (
                                            <label key={a.value} className="checkbox-item">
                                                <input
                                                    type="checkbox"
                                                    value={a.value}
                                                    checked={formData.allergies.includes(a.value)}
                                                    onChange={handleAllergyChange}
                                                /> {a.label}
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                    <button type="button" className="btn btn-outline" onClick={prevStep}>
                                        <i className="fas fa-arrow-left"></i> Anterior
                                    </button>
                                    <button type="button" className="btn btn-primary" onClick={nextStep}>
                                        <i className="fas fa-arrow-right"></i> Siguiente
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* STEP 4: Plan Type */}
                        {step === 4 && (
                            <div className="calc-card animate-in">
                                <h2><i className="fas fa-clipboard-list" style={{ color: 'var(--accent)' }}></i> Tipo de plan</h2>
                                <p style={{ color: 'var(--text-secondary)', marginBottom: '24px', fontSize: '0.9rem' }}>
                                    ¿Quieres una receta individual o un plan completo del día?
                                </p>

                                <div className="objective-cards" style={{ gridTemplateColumns: '1fr 1fr' }}>
                                    {[
                                        { value: 'completo', icon: 'fas fa-calendar-day', title: 'Plan completo', desc: 'Desayuno, comida, cena y snack. Día completo planificado.' },
                                        { value: 'receta', icon: 'fas fa-utensils', title: 'Solo una receta', desc: 'Una receta individual adaptada a tus calorías.' },
                                    ].map(pt => (
                                        <div
                                            key={pt.value}
                                            className={`objective-card ${formData.plan_type === pt.value ? 'selected' : ''}`}
                                            onClick={() => setFormData(prev => ({ ...prev, plan_type: pt.value }))}
                                        >
                                            <i className={pt.icon}></i>
                                            <h4>{pt.title}</h4>
                                            <p>{pt.desc}</p>
                                        </div>
                                    ))}
                                </div>

                                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                    <button type="button" className="btn btn-outline" onClick={prevStep}>
                                        <i className="fas fa-arrow-left"></i> Anterior
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-primary btn-lg"
                                        onClick={handleSubmit}
                                        disabled={loading}
                                    >
                                        <i className={loading ? 'fas fa-spinner fa-spin' : 'fas fa-magic'}></i> {loading ? 'Generando...' : 'Generar mi plan'}
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                ) : (
                    /* RESULTS */
                    <div className="calc-container">
                        <div className="results-card animate-in">
                            <h2 style={{ textAlign: 'center', marginBottom: '24px' }}>
                                <i className="fas fa-chart-bar" style={{ color: 'var(--accent)' }}></i> Tu resumen nutricional
                            </h2>

                            <div className="calorie-display">
                                <div className="number">{result.target}</div>
                                <div className="unit">kcal / día (objetivo: {result.objetivo})</div>
                                <p style={{ fontSize: '0.82rem', color: 'var(--text-muted)', marginTop: '8px' }}>
                                    BMR: {result.bmr} kcal → TDEE: {result.tdee} kcal
                                </p>
                            </div>

                            <div className="macro-grid">
                                <div className="macro-item">
                                    <div className="macro-value">{result.protein}g</div>
                                    <div className="macro-label">Proteínas</div>
                                </div>
                                <div className="macro-item">
                                    <div className="macro-value">{result.carbs}g</div>
                                    <div className="macro-label">Carbohidratos</div>
                                </div>
                                <div className="macro-item">
                                    <div className="macro-value">{result.fat}g</div>
                                    <div className="macro-label">Grasas</div>
                                </div>
                                <div className="macro-item">
                                    <div className="macro-value">{result.target}</div>
                                    <div className="macro-label">Kcal totales</div>
                                </div>
                            </div>
                        </div>

                        {/* Generated Plan */}
                        {plan && (
                            <div className="meal-plan animate-in">
                                {plan.tipo === 'completo' ? (
                                    <>
                                        <h2><i className="fas fa-calendar-day" style={{ color: 'var(--accent)' }}></i> Tu plan completo del día</h2>
                                        {plan.recetas && Object.entries(plan.recetas).map(([cat, r]) => (
                                            <Link to={`/recipes/${r.id}`} key={cat} className="meal-card">
                                                <div className="meal-card-image">
                                                    <img src={r.image_url} alt={r.title} loading="lazy" />
                                                </div>
                                                <div className="meal-card-body">
                                                    <div className="meal-type">{categoryLabels[cat] || cat}</div>
                                                    <h4>{r.title}</h4>
                                                    <p>{r.description}</p>
                                                </div>
                                                <div className="meal-card-cal">
                                                    {r.calories}
                                                    <small>kcal</small>
                                                </div>
                                            </Link>
                                        ))}
                                        {plan.recetas && (
                                            <div className="results-card" style={{ marginTop: '16px', textAlign: 'center' }}>
                                                <strong style={{ fontSize: '1.1rem' }}>
                                                    Total del plan: <span style={{ color: 'var(--accent-light)' }}>
                                                        {Object.values(plan.recetas).reduce((sum, r) => sum + r.calories, 0)} kcal
                                                    </span>
                                                </strong>
                                                <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}> / {result.target} kcal objetivo</span>
                                            </div>
                                        )}
                                    </>
                                ) : plan.tipo === 'receta' && plan.recetas && plan.recetas.length > 0 ? (
                                    <>
                                        <h2><i className="fas fa-utensils" style={{ color: 'var(--accent)' }}></i> Tu receta recomendada</h2>
                                        <Link to={`/recipes/${plan.recetas[0].id}`} className="meal-card">
                                            <div className="meal-card-image">
                                                <img src={plan.recetas[0].image_url} alt={plan.recetas[0].title} loading="lazy" />
                                            </div>
                                            <div className="meal-card-body">
                                                <div className="meal-type">{plan.recetas[0].category}</div>
                                                <h4>{plan.recetas[0].title}</h4>
                                                <p>{plan.recetas[0].description}</p>
                                            </div>
                                            <div className="meal-card-cal">
                                                {plan.recetas[0].calories}
                                                <small>kcal</small>
                                            </div>
                                        </Link>
                                    </>
                                ) : (
                                    <div className="empty-state">
                                        <i className="fas fa-search"></i>
                                        <h3>No se encontraron recetas</h3>
                                        <p>No hay recetas que coincidan con tus filtros. Prueba con menos restricciones.</p>
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="text-center mt-3 no-print" style={{ display: 'flex', gap: '12px', justifyContent: 'center' }}>
                            <button onClick={() => window.print()} className="btn btn-primary btn-lg">
                                <i className="fas fa-print"></i> Imprimir plan
                            </button>
                            <button onClick={resetCalc} className="btn btn-outline btn-lg">
                                <i className="fas fa-redo"></i> Nuevo cálculo
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
