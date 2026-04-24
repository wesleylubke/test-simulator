    </div>
</main>

<?php
$user = class_exists(\App\AuthService::class) ? \App\AuthService::user() : null;
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>

<?php if ($user !== null): ?>
    <nav class="app-mobile-nav is-hidden-desktop">
        <a class="<?= $currentPage === 'index.php' ? 'is-current' : '' ?>" href="/index.php">
            <span>☰</span>
            <small>Provas</small>
        </a>
        <a class="<?= $currentPage === 'attempts.php' ? 'is-current' : '' ?>" href="/attempts.php">
            <span>↺</span>
            <small>Histórico</small>
        </a>
        <a href="/logout.php">
            <span>●</span>
            <small>Sair</small>
        </a>
    </nav>
<?php endif; ?>

</body>
</html>