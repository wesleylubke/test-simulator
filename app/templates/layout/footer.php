</div>
</section>

<?php if (class_exists(\App\AuthService::class) && \App\AuthService::user() !== null): ?>
    <nav class="app-bottom-nav">
        <a class="is-active" href="/index.php">☰<br>Exams</a>
        <a href="/attempts.php">↺<br>History</a>
        <a href="/logout.php">●<br>Sair</a>
    </nav>
<?php endif; ?>

</body>
</html>