<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\AuthService;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errorMessage = null;

if (!empty($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new RuntimeException('Informe e-mail e senha.');
        }

        $auth = new AuthService();
        $user = $auth->signIn($email, $password);

        $_SESSION['user'] = [
            'uid' => $user['uid'],
            'email' => $user['email'],
            'idToken' => $user['idToken'],
        ];

        header('Location: /index.php');
        exit;
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

$pageTitle = 'Login - Test Simulator';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark" class="theme-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=dashboard-dark-1">
</head>
<body>

<main class="login-page">
    <section class="login-card">
        <div class="login-brand">
            <div class="app-logo">✓</div>
            <div>
                <h1>Test Simulator</h1>
                <p>Acesse sua área de estudos</p>
            </div>
        </div>

        <?php if ($errorMessage !== null): ?>
            <div class="notification is-danger is-light">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label class="label">E-mail</label>
                <div class="control">
                    <input
                        class="input"
                        type="email"
                        name="email"
                        placeholder="seu@email.com"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <div class="field">
                <label class="label">Senha</label>
                <div class="control">
                    <input
                        class="input"
                        type="password"
                        name="password"
                        placeholder="Digite sua senha"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <button class="button app-button-primary is-fullwidth mt-4" type="submit">
                Entrar
            </button>
        </form>
    </section>
</main>

</body>
</html>