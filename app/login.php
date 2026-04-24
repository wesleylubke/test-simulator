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
include __DIR__ . '/templates/layout/header.php';
?>

<div class="columns is-centered">
    <div class="column is-5">
        <div class="box">
            <h1 class="title is-4">Login</h1>

            <?php if ($errorMessage !== null): ?>
                <div class="notification is-danger is-light">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="field">
                    <label class="label">E-mail</label>
                    <div class="control">
                        <input class="input" type="email" name="email" required>
                    </div>
                </div>

                <div class="field">
                    <label class="label">Senha</label>
                    <div class="control">
                        <input class="input" type="password" name="password" required>
                    </div>
                </div>

                <button class="button is-dark is-fullwidth" type="submit">
                    Entrar
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/layout/footer.php'; ?>