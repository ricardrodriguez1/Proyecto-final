    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="<?= baseUrl() ?>/index.php" class="footer-logo">
                        <i class="fas fa-leaf"></i> Nutri<span>Fit</span>
                    </a>
                    <p>Tu plataforma inteligente de recetas y planificación alimentaria personalizada.</p>
                </div>
                <div class="footer-links">
                    <h4>Navegación</h4>
                    <ul>
                        <li><a href="<?= baseUrl() ?>/index.php">Inicio</a></li>
                        <li><a href="<?= baseUrl() ?>/recipes/index.php">Recetas</a></li>
                        <li><a href="<?= baseUrl() ?>/calculator/index.php">Calculadora</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Política de privacidad</a></li>
                        <li><a href="#">Términos de uso</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> NutriFit. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="<?= baseUrl() ?>/js/app.js"></script>
</body>
</html>
