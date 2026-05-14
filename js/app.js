// ============================================
// NutriFit — Main JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initCalculator();
    initDynamicLists();
    initCheckboxItems();
    initAnimations();
});

// --- Mobile Menu ---
function initMobileMenu() {
    const toggle = document.getElementById('navToggle');
    const menu = document.getElementById('navMenu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', () => {
        menu.classList.toggle('active');
        toggle.classList.toggle('active');
    });

    // Close menu on link click
    menu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('active');
            toggle.classList.remove('active');
        });
    });
}

// --- Calculator Multi-Step ---
function initCalculator() {
    const form = document.getElementById('calcForm');
    if (!form) return;

    const steps = form.querySelectorAll('.calc-step-content');
    const stepIndicators = document.querySelectorAll('.calc-step');
    const nextBtns = form.querySelectorAll('.btn-next');
    const prevBtns = form.querySelectorAll('.btn-prev');
    let currentStep = 0;

    function showStep(index) {
        steps.forEach((s, i) => {
            s.style.display = i === index ? 'block' : 'none';
            s.style.animation = i === index ? 'fadeInUp 0.4s ease' : 'none';
        });
        stepIndicators.forEach((s, i) => {
            s.classList.toggle('active', i === index);
            s.classList.toggle('completed', i < index);
        });
        currentStep = index;
    }

    nextBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentStep < steps.length - 1) {
                // Validate current step
                if (validateStep(currentStep)) {
                    showStep(currentStep + 1);
                }
            }
        });
    });

    prevBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentStep > 0) showStep(currentStep - 1);
        });
    });

    showStep(0);

    // Objective cards selection
    document.querySelectorAll('.objective-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.objective-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const input = document.getElementById('objetivo');
            if (input) input.value = card.dataset.value;
        });
    });

    // Plan type cards
    document.querySelectorAll('.plan-type-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.plan-type-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const input = document.getElementById('planType');
            if (input) input.value = card.dataset.value;
        });
    });

    // Live BMR preview
    const bmrFields = ['genero', 'peso', 'altura', 'edad', 'actividad'];
    bmrFields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', updateBMRPreview);
        if (el) el.addEventListener('input', updateBMRPreview);
    });
}

function validateStep(step) {
    const stepContent = document.querySelectorAll('.calc-step-content')[step];
    const required = stepContent.querySelectorAll('[required]');
    let valid = true;
    required.forEach(input => {
        if (!input.value) {
            input.style.borderColor = '#ef4444';
            valid = false;
            setTimeout(() => { input.style.borderColor = ''; }, 2000);
        }
    });

    if (step === 1) {
        const obj = document.getElementById('objetivo');
        if (!obj || !obj.value) {
            alert('Por favor, selecciona un objetivo.');
            valid = false;
        }
    }

    return valid;
}

function updateBMRPreview() {
    const genero = document.getElementById('genero')?.value;
    const peso = parseFloat(document.getElementById('peso')?.value);
    const altura = parseFloat(document.getElementById('altura')?.value);
    const edad = parseInt(document.getElementById('edad')?.value);
    const actividad = document.getElementById('actividad')?.value;

    if (!genero || !peso || !altura || !edad || !actividad) return;

    let bmr;
    if (genero === 'male') {
        bmr = (10 * peso) + (6.25 * altura) - (5 * edad) + 5;
    } else {
        bmr = (10 * peso) + (6.25 * altura) - (5 * edad) - 161;
    }

    const multipliers = {
        sedentario: 1.2,
        ligero: 1.375,
        moderado: 1.55,
        activo: 1.725,
        muy_activo: 1.9
    };

    const tdee = Math.round(bmr * (multipliers[actividad] || 1.2));

    const preview = document.getElementById('bmrPreview');
    if (preview) {
        preview.innerHTML = `<strong>${Math.round(bmr)}</strong> kcal (BMR) → <strong>${tdee}</strong> kcal/día (TDEE)`;
        preview.style.display = 'block';
    }
}

// --- Dynamic Lists for Admin Form ---
function initDynamicLists() {
    document.querySelectorAll('.add-ingredient-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const list = btn.closest('.dynamic-list').querySelector('.items-container');
            const index = list.children.length;
            const row = document.createElement('div');
            row.className = 'item-row';
            row.innerHTML = `
                <input type="text" name="ingredients[${index}][name]" placeholder="Ingrediente" required>
                <input type="text" name="ingredients[${index}][quantity]" placeholder="Cantidad">
                <input type="text" name="ingredients[${index}][unit]" placeholder="Unidad">
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            list.appendChild(row);
        });
    });

    document.querySelectorAll('.add-step-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const list = btn.closest('.dynamic-list').querySelector('.items-container');
            const index = list.children.length;
            const row = document.createElement('div');
            row.className = 'item-row';
            row.style.gridTemplateColumns = '1fr 40px';
            row.innerHTML = `
                <textarea name="steps[${index}]" placeholder="Paso ${index + 1}..." rows="2" required></textarea>
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            list.appendChild(row);
        });
    });
}

// --- Checkbox Items Toggle ---
function initCheckboxItems() {
    document.querySelectorAll('.checkbox-item').forEach(item => {
        const input = item.querySelector('input');
        if (!input) return;

        const updateState = () => {
            item.classList.toggle('active', input.checked);
        };

        input.addEventListener('change', updateState);
        updateState();
    });
}

// --- Scroll Animations ---
function initAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.feature-card, .recipe-card, .stat-card').forEach(el => {
        observer.observe(el);
    });
}

// --- Print Plan ---
function printPlan() {
    window.print();
}

// --- Confirm Delete ---
function confirmDelete(name) {
    return confirm(`¿Estás seguro de que deseas eliminar "${name}"? Esta acción no se puede deshacer.`);
}
