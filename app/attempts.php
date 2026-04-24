<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\AuthService;

AuthService::requireLogin();

use App\FirestoreRestRepository;
use App\GoogleAccessTokenService;

$pageTitle = 'Histórico de Tentativas - Test Simulator';
$errorMessage = null;
$attempts = [];

try {
    $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

    if (!is_string($credentialsPath) || $credentialsPath === '') {
        throw new RuntimeException('Variável GOOGLE_APPLICATION_CREDENTIALS não configurada.');
    }

    $tokenService = new GoogleAccessTokenService($credentialsPath);
    $repository = new FirestoreRestRepository($tokenService);

    $attempts = $repository->listAttempts();
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}

include __DIR__ . '/templates/layout/header.php';
?>

<div class="mb-5">
    <a class="button is-light" href="/index.php">← Voltar para provas</a>
</div>

<div class="box">
    <h1 class="title is-4">Histórico de tentativas</h1>

    <?php if ($errorMessage !== null): ?>
        <div class="notification is-danger is-light">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php elseif (empty($attempts)): ?>
        <p class="has-text-grey">Nenhuma tentativa registrada ainda.</p>
    <?php else: ?>
        <div class="table-container">
            <table class="table is-fullwidth is-striped is-hoverable">
                <thead>
                    <tr>
                        <th>Prova</th>
                        <th>Acertos</th>
                        <th>Erros</th>
                        <th>Total</th>
                        <th>Nota</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $attempt): ?>
                        <tr>
                            <td>
                                <a href="/exam.php?id=<?= urlencode((string) $attempt['exam_id']) ?>">
                                    <?= htmlspecialchars((string) $attempt['exam_title']) ?>
                                </a>
                            </td>
                            <td><?= (int) $attempt['total_correct'] ?></td>
                            <td><?= (int) $attempt['total_wrong'] ?></td>
                            <td><?= (int) $attempt['total_questions'] ?></td>
                            <td><?= htmlspecialchars((string) $attempt['score_percent']) ?>%</td>
                            <td><?= htmlspecialchars((string) $attempt['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/layout/footer.php'; ?>