<nav class="bottom-nav">
    <div class="nav-rail">
        <a href="issues.php" class="nav-icon<?php echo ($navActive ?? '') === 'issues' ? ' active' : ''; ?>" aria-label="Pranešimai">
            <span class="icon" aria-hidden="true">
                <img src="../icons/newspaper.svg" alt="">
            </span>
            <span class="label">Pranešimai</span>
        </a>
        <a href="index.php" class="nav-icon<?php echo ($navActive ?? '') === 'map' ? ' active' : ''; ?>" aria-label="Žemėlapis">
            <span class="icon" aria-hidden="true">
                <img src="../icons/map.svg" alt="">
            </span>
            <span class="label">Žemėlapis</span>
        </a>
        <a href="login.php" class="nav-icon nav-account<?php echo ($navActive ?? '') === 'account' ? ' active' : ''; ?>" aria-label="Profilis">
            <span class="icon" aria-hidden="true">
                <img src="../icons/user.svg" alt="">
            </span>
            <span class="label">Paskyra</span>
        </a>
        <a href="settings.php" class="nav-icon<?php echo ($navActive ?? '') === 'settings' ? ' active' : ''; ?>" aria-label="Nustatymai">
            <span class="icon" aria-hidden="true">
                <img src="../icons/menu-burger.svg" alt="">
            </span>
            <span class="label">Nustatymai</span>
        </a>
    </div>
    <?php if (!empty($navFab)): ?>
        <button class="fab" id="fab-toggle" aria-label="Pridėti pranešimą">
            <img src="../icons/add.svg" alt="">
        </button>
    <?php endif; ?>
</nav>
