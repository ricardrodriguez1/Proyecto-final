// ============================================
// NutriFit — Recipe Card Component
// Reusable card used in Home and Recipes pages
// ============================================

import { Link } from 'react-router-dom';

export default function RecipeCard({ recipe }) {
    return (
        <Link to={`/recipes/${recipe.id}`} className="recipe-card">
            <div className="recipe-card-image">
                <img src={recipe.image_url} alt={recipe.title} loading="lazy" />
                <span className="recipe-card-badge">{recipe.category}</span>
            </div>
            <div className="recipe-card-body">
                <h3>{recipe.title}</h3>
                <p>{recipe.description}</p>
                <div className="recipe-meta">
                    <span><i className="fas fa-clock"></i> {recipe.prep_time + recipe.cook_time} min</span>
                    <span><i className="fas fa-fire"></i> {recipe.calories} kcal</span>
                    <span><i className="fas fa-signal"></i> {recipe.difficulty}</span>
                </div>
                {recipe.tags && recipe.tags.length > 0 && (
                    <div className="recipe-tags">
                        {recipe.tags.slice(0, 3).map((tag, i) => (
                            <span key={i} className="tag">{tag}</span>
                        ))}
                    </div>
                )}
            </div>
        </Link>
    );
}
