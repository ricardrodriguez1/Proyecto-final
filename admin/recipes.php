<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAdmin();

$db = getDB();
$recipes = $db->query("SELECT * FROM recipes ORDER BY id DESC")->fetchAll();

$pageTitle = 'Gestión de Recetas';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-page">
    <div class="container">
        <div class="admin-header">
            <h1><i class="fas fa-utensils" style="color:var(--accent)"></i> Gestión de Recetas</h1>
            <a href="recipe_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva receta</a>
        </div>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Dieta</th>
                        <th>Kcal</th>
                        <th>Dificultad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipes as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><strong><?= sanitize($r['title']) ?></strong></td>
                        <td><?= sanitize($r['category']) ?></td>
                        <td><?= sanitize($r['diet_type']) ?></td>
                        <td><?= $r['calories'] ?></td>
                        <td><?= sanitize($r['difficulty']) ?></td>
                        <td>
                            <div class="actions">
                                <a href="recipe_form.php?id=<?= $r['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Editar</a>
                                <a href="delete_recipe.php?id=<?= $r['id'] ?>" class="btn-delete" onclick="return confirmDelete('<?= sanitize($r['title']) ?>')"><i class="fas fa-trash"></i> Eliminar</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver al panel</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
