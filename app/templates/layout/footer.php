</div>
</section>

<?php
$user = class_exists(\App\AuthService::class) ? \App\AuthService::user() : null;
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>

<?php if ($user !== null): ?>
    <nav class="bottom-nav">
        <a class="<?= $currentPage === 'index.php' ? 'is-active' : '' ?>" href="/index.php">
            ☰<br>Provas
        </a>
        <a class="<?= $currentPage === 'attempts.php' ? 'is-active' : '' ?>" href="/attempts.php">
            ↺<br>Histórico
        </a>
        <a href="/logout.php">
            ●<br>Sair
        </a>
    </nav>
<?php endif; ?>

</body>
</html>