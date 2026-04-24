<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\AuthService;
use App\FirestoreRestRepository;
use App\GoogleAccessTokenService;
use App\ValidationException;

AuthService::requireLogin();

$examId = trim((string) ($_GET['id'] ?? $_POST['exam_id'] ?? ''));
$errorMessage = null;
$successMessage = null;
$exam = null;

function repository(): FirestoreRestRepository
{
    $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

    if (!is_string($credentialsPath) || $credentialsPath === '') {
        throw new ValidationException('Variável GOOGLE_APPLICATION_CREDENTIALS não configurada.');
    }

    return new FirestoreRestRepository(
        new GoogleAccessTokenService($credentialsPath)
    );
}

try {
    if ($examId === '') {
        throw new ValidationException('ID da prova não informado.');
    }

    $repo = repository();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim((string) ($_POST['title'] ?? ''));

        if ($title === '') {
            throw new ValidationException('O título da prova é obrigatório.');
        }

        $repo->updateExamTitle($examId, $title);

        $successMessage = 'Prova atualizada com sucesso.';
    }

    $exam = $repo->getExam($examId);
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}

$pageTitle = 'Editar prova - Test Simulator';
include __DIR__ . '/templates/layout/header.php';
?>

<div class="mb-5">
    <a class="button is-light" href="/index.php">← Voltar para provas</a>
</div>

<div class="box">
    <h1 class="title is-4">Editar prova</h1>

    <?php if ($successMessage !== null): ?>
        <div class="notification is-success is-light">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== null): ?>
        <div class="notification is-danger is-light">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($exam !== null): ?>
        <form method="post">
            <input type="hidden" name="exam_id" value="<?= htmlspecialchars((string) $exam['id']) ?>">

            <div class="field">
                <label class="label">Título da prova</label>
                <div class="control">
                    <input
                        class="input"
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars((string) $exam['title']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="field">
                <label class="label">ID</label>
                <div class="control">
                    <input class="input" type="text" value="<?= htmlspecialchars((string) $exam['id']) ?>" disabled>
                </div>
            </div>

            <div class="field">
                <label class="label">Total de questões</label>
                <div class="control">
                    <input class="input" type="text" value="<?= (int) $exam['total_questions'] ?>" disabled>
                </div>
            </div>

            <div class="buttons mt-4">
                <button class="button is-dark" type="submit">Salvar alterações</button>
                <a class="button is-light" href="/index.php">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/layout/footer.php'; ?>