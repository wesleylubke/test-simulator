<?php
$user = class_exists(\App\AuthService::class) ? \App\AuthService::user() : null;
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

    <div class="navbar-menu is-active desktop-only">
        <div class="navbar-start">
            <?php if ($user !== null): ?>
                <a class="navbar-item" href="/index.php">Exams</a>
                <a class="navbar-item" href="/attempts.php">History</a>
            <?php endif; ?>
        </div>

        <div class="navbar-end">
            <?php if ($user !== null): ?>
                <div class="navbar-item">
                    <?= htmlspecialchars((string) $user['email']) ?>
                </div>
                <div class="navbar-item">
                    <a class="button is-light is-small" href="/logout.php">Sair</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="section">
<div class="container">