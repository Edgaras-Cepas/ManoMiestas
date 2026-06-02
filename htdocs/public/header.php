
<header class="topbar d-flex align-items-center">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <a href="./" class="brand d-flex align-items-center text-decoration-none">
            <div class="brand-mark me-3"></div>
            <span class="brand-name">ManoMiestas</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <?php if (is_admin()): ?>
                <a href="admin.php" class="admin-link" aria-label="Admin">
                    <img src="../icons/admin.svg" alt="">
                </a>
            <?php endif; ?>
            <?php if (basename($_SERVER['PHP_SELF']) == 'issue.php'): ?>
            <a href="issues.php" class="back-link text-white text-decoration-none" aria-label="Atgal i sarasa">
                <img src="../icons/back-arrow.svg" alt="">
            </a>
            <?php endif; ?>
            <?php if (basename($_SERVER['PHP_SELF']) == 'my-issues.php'): ?>
            <a href="settings.php" class="back-link text-white text-decoration-none" aria-label="Atgal i nustatymus">
                <img src="../icons/back-arrow.svg" alt="">
            </a>
            <?php endif; ?>
            <?php if (basename($_SERVER['PHP_SELF']) == 'admin.php'): ?>
            <a href="index.php" class="back-link text-white text-decoration-none" aria-label="Atgal">
                <img src="../icons/back-arrow.svg" alt="">
            </a>
            <?php endif; ?>
        </div>
    </div>
</header>