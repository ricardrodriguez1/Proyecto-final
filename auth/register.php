<?php
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $db = getDB();
        // Check if username or email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            $error = 'El nombre de usuario o email ya están registrados.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (:u, :e, :p, 'user')");
            $stmt->execute([':u' => $username, ':e' => $email, ':p' => $hashedPassword]);
            setFlash('success', '¡Registro exitoso! Ya puedes iniciar sesión.');
            header("Location: login.php");
            exit;
        }
    }
}

$pageTitle = 'Registro';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card animate-in">
        <h1><i class="fas fa-user-plus" style="color:var(--accent)"></i> Registro</h1>
        <p class="subtitle">Crea tu cuenta y empieza a planificar tu alimentación</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin:0 0 20px;padding:12px;border-radius:var(--radius-sm)">
                <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Nombre de usuario</label>
                <input type="text" id="username" name="username" value="<?= sanitize($username ?? '') ?>" required placeholder="Tu nombre de usuario">
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" value="<?= sanitize($email ?? '') ?>" required placeholder="tu@email.com">
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres">
            </div>
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repite la contraseña">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-user-plus"></i> Crear cuenta
            </button>
        </form>

        <div class="auth-footer">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
