<?php
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Introduce tu usuario y contraseña.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :u1 OR email = :u2");
        $stmt->execute([':u1' => $username, ':u2' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            setFlash('success', '¡Bienvenido, ' . $user['username'] . '!');
            header("Location: ../index.php");
            exit;
        } else {
            $error = 'Credenciales incorrectas. Inténtalo de nuevo.';
        }
    }
}

$pageTitle = 'Iniciar sesión';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card animate-in">
        <h1><i class="fas fa-sign-in-alt" style="color:var(--accent)"></i> Entrar</h1>
        <p class="subtitle">Accede a tu cuenta para gestionar tu alimentación</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin:0 0 20px;padding:12px;border-radius:var(--radius-sm)">
                <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Usuario o email</label>
                <input type="text" id="username" name="username" value="<?= sanitize($username ?? '') ?>" required placeholder="Tu usuario o email">
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="Tu contraseña">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-sign-in-alt"></i> Iniciar sesión
            </button>
        </form>

        <div class="auth-footer">
            ¿No tienes cuenta? <a href="register.php">Regístrate gratis</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
