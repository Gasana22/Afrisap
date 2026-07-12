    </main>
    <footer class="site-footer">
        <div class="site-footer-inner">
            <div>
                <div class="site-footer-brand"><?= e(APP_NAME) ?></div>
                <p class="site-footer-tag">Farm-to-buyer traceability and day-to-day operations for cooperatives and farm organizations.</p>
            </div>
            <nav>
                <a href="<?= BASE_URL ?>/about.php">About</a>
                <a href="<?= BASE_URL ?>/trace.php">Track a Product</a>
                <a href="<?= BASE_URL ?>/contact.php">Contact</a>
                <a href="<?= BASE_URL ?>/admin-login.php">Staff Login</a>
            </nav>
        </div>
        <p class="site-footer-bottom">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Every farm's data stays private to their own organization.</p>
    </footer>
    <script src="<?= BASE_URL ?>/assets/js/site.js" defer></script>
</body>
</html>
