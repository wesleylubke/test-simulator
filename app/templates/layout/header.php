<?php
$user = class_exists(\App\AuthService::class) ? \App\AuthService::user() : null;
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Test Simulator') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<nav class="navbar app-navbar">
    <div class="navbar-brand">
        <a class="navbar-item" href="/index.php">
            <strong>Test Simulator</strong>
        </a>
    </div>

    <div class="navbar-menu is-active">
        <?php if ($user !== null): ?>
            <div class="navbar-start is-hidden-touch">
                <a class="navbar-item <?= $currentPage === 'index.php' ? 'has-text-weight-bold' : '' ?>" href="/index.php">
                    Provas
                </a>
                <a class="navbar-item <?= $currentPage === 'attempts.php' ? 'has-text-weight-bold' : '' ?>" href="/attempts.php">
                    Histórico
                </a>
            </div>

            <div class="navbar-end is-hidden-touch">
                <div class="navbar-item has-text-grey">
                    <?= htmlspecialchars((string) ($user['email'] ?? '')) ?>
                </div>
                <div class="navbar-item">
                    <a class="button is-light is-small" href="/logout.php">Sair</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<section class="section">
<div class="container app-shell">