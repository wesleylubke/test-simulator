<?php
$user = class_exists(\App\AuthService::class) ? \App\AuthService::user() : null;
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark" class="theme-dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Test Simulator') ?></title>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=dark-20260424">
</head>

<body>

    <nav class="navbar app-topbar" role="navigation">
        <div class="container app-container">
            <div class="navbar-brand">
                <a class="navbar-item app-brand" href="/index.php">Test Simulator</a>
            </div>

            <?php if ($user !== null): ?>
                <div class="navbar-menu is-active app-menu">
                    <div class="navbar-start">
                        <a class="navbar-item app-nav-link <?= $currentPage === 'index.php' ? 'is-current' : '' ?>" href="/index.php">
                            Provas
                        </a>
                        <a class="navbar-item app-nav-link <?= $currentPage === 'attempts.php' ? 'is-current' : '' ?>" href="/attempts.php">
                            Histórico
                        </a>
                    </div>

                    <div class="navbar-end is-hidden-touch">
                        <div class="navbar-item app-user">
                            <?= htmlspecialchars((string) ($user['email'] ?? '')) ?>
                        </div>
                        <div class="navbar-item">
                            <a class="button is-small is-light" href="/logout.php">Sair</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="section">
        <div class="container app-container">