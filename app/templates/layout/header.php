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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=dashboard-dark-1">
</head>
<body>

<div class="app-layout">
    <?php if ($user !== null): ?>
        <aside class="app-sidebar">
            <div class="app-sidebar-brand">
                <div class="app-logo">✓</div>
                <div>
                    <strong>Test Simulator</strong>
                    <small>Study dashboard</small>
                </div>
            </div>

            <nav class="app-sidebar-nav">
                <a class="<?= $currentPage === 'index.php' ? 'is-active' : '' ?>" href="/index.php">
                    <span>⌂</span>
                    <span>Provas</span>
                </a>

                <a class="<?= $currentPage === 'attempts.php' ? 'is-active' : '' ?>" href="/attempts.php">
                    <span>↺</span>
                    <span>Histórico</span>
                </a>

                <a href="/logout.php">
                    <span>⇥</span>
                    <span>Sair</span>
                </a>
            </nav>
        </aside>
    <?php endif; ?>

    <main class="app-main <?= $user === null ? 'is-auth-page' : '' ?>">
        <?php if ($user !== null): ?>
            <header class="app-topbar">
                <div>
                    <strong><?= htmlspecialchars($pageHeader ?? 'Test Simulator') ?></strong>
                </div>

                <div class="app-user-menu">
                    <span><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></span>
                    <a class="button is-small app-button-ghost" href="/logout.php">Sair</a>
                </div>
            </header>
        <?php endif; ?>

        <section class="app-content">