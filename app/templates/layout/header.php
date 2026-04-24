<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Test Simulator') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
</head>

<body>

  <nav class="navbar is-dark">
    <div class="navbar-brand">
      <a class="navbar-item" href="/index.php">
        <strong>Test Simulator</strong>
      </a>
      <a class="navbar-item" href="/attempts.php">
        Histórico
      </a>
    </div>
  </nav>

  <section class="section">
    <div class="container">