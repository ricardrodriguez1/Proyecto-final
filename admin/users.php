<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
requireAdmin();

$db = getDB();
$users = $db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();

$pageTitle = 'Gestión de Usuarios';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-page">
    <div class="container">
        <div class="admin-header">
            <h1><i class="fas fa-users" style="color:var(--accent)"></i> Gestión de Usuarios</h1>
        </div>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= sanitize($u['username']) ?></strong></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td>
                            <span class="tag" style="<?= $u['role'] === 'admin' ? 'background:rgba(245,158,11,0.15);color:#fbbf24;border-color:rgba(245,158,11,0.2)' : '' ?>">
                                <?= sanitize($u['role']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="delete_user.php?id=<?= $u['id'] ?>" class="btn-delete" onclick="return confirmDelete('<?= sanitize($u['username']) ?>')"><i class="fas fa-trash"></i> Eliminar</a>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:0.8rem">Tu cuenta</span>
                                <?php endif; ?>
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
