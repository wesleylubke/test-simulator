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
$questions = [];
$folders = [];

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

        $folderId = trim((string) ($_POST['folder_id'] ?? ''));

        $folderName = 'Sem pasta';

        if ($folderId !== '') {
            foreach ($repo->listFolders() as $folder) {
                if ((string) $folder['id'] === $folderId) {
                    $folderName = (string) $folder['name'];
                    break;
                }
            }
        }

        $repo->updateExamFolder($examId, $folderId, $folderName);

        $postedQuestions = $_POST['questions'] ?? [];

        if (!is_array($postedQuestions)) {
            throw new ValidationException('Formato inválido das questões.');
        }

        foreach ($postedQuestions as $questionId => $questionData) {
            if (!is_array($questionData)) {
                continue;
            }

            $type = trim((string) ($questionData['type'] ?? ''));
            $statement = trim((string) ($questionData['statement'] ?? ''));
            $correctAnswer = trim((string) ($questionData['correct_answer'] ?? ''));
            $explanation = trim((string) ($questionData['explanation'] ?? ''));

            if ($statement === '') {
                throw new ValidationException("A questão {$questionId} está sem enunciado.");
            }

            if ($correctAnswer === '') {
                throw new ValidationException("A questão {$questionId} está sem resposta correta.");
            }

            $options = [];

            if ($type === 'multiple_choice') {
                $options = [
                    'A' => trim((string) ($questionData['option_a'] ?? '')),
                    'B' => trim((string) ($questionData['option_b'] ?? '')),
                    'C' => trim((string) ($questionData['option_c'] ?? '')),
                    'D' => trim((string) ($questionData['option_d'] ?? '')),
                ];

                foreach ($options as $optionKey => $optionValue) {
                    if ($optionValue === '') {
                        throw new ValidationException("A alternativa {$optionKey} da questão {$questionId} está vazia.");
                    }
                }

                if (!in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
                    throw new ValidationException("A resposta correta da questão {$questionId} deve ser A, B, C ou D.");
                }
            }

            if ($type === 'fill_blank') {
                $options = [];
            }

            if (!in_array($type, ['multiple_choice', 'fill_blank'], true)) {
                throw new ValidationException("Tipo inválido na questão {$questionId}.");
            }

            $repo->updateQuestion($examId, (string) $questionId, [
                'statement' => $statement,
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation,
            ]);
        }

        $successMessage = 'Prova e questões atualizadas com sucesso.';
    }

    $exam = $repo->getExam($examId);
    $questions = $repo->listQuestions($examId);

    $folders = $repo->listFolders();
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
                        required>
                </div>
            </div>

            <div class="field">
                <label class="label">Pasta</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="folder_id">
                            <option value="">Sem pasta</option>

                            <?php foreach ($folders as $folder): ?>
                                <option
                                    value="<?= htmlspecialchars((string) $folder['id']) ?>"
                                    <?= (string) ($exam['folder_id'] ?? '') === (string) $folder['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $folder['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column">
                    <div class="notification is-light">
                        <strong>ID</strong><br>
                        <?= htmlspecialchars((string) $exam['id']) ?>
                    </div>
                </div>

                <div class="column">
                    <div class="notification is-light">
                        <strong>Total de questões</strong><br>
                        <?= (int) $exam['total_questions'] ?>
                    </div>
                </div>

                <div class="column">
                    <div class="notification is-light">
                        <strong>Status</strong><br>
                        <?= htmlspecialchars((string) ($exam['status'] ?? '')) ?>
                    </div>
                </div>
            </div>

            <hr>

            <h2 class="title is-4">Questões</h2>

            <?php if (empty($questions)): ?>
                <div class="notification is-warning is-light">
                    Nenhuma questão encontrada para esta prova.
                </div>
            <?php else: ?>
                <?php foreach ($questions as $question): ?>
                    <?php
                    $questionId = (string) $question['question_id'];
                    $type = (string) $question['type'];
                    $options = (array) ($question['options'] ?? []);
                    ?>

                    <div class="box">
                        <p class="is-size-7 has-text-grey mb-3">
                            <?= htmlspecialchars($questionId) ?>
                            ·
                            <?= htmlspecialchars($type) ?>
                            ·
                            ordem <?= (int) $question['order_index'] ?>
                        </p>

                        <input
                            type="hidden"
                            name="questions[<?= htmlspecialchars($questionId) ?>][type]"
                            value="<?= htmlspecialchars($type) ?>">

                        <div class="field">
                            <label class="label">Enunciado</label>
                            <div class="control">
                                <textarea
                                    class="textarea"
                                    name="questions[<?= htmlspecialchars($questionId) ?>][statement]"
                                    required><?= htmlspecialchars((string) $question['statement']) ?></textarea>
                            </div>
                        </div>

                        <?php if ($type === 'multiple_choice'): ?>
                            <div class="columns">
                                <div class="column">
                                    <div class="field">
                                        <label class="label">Alternativa A</label>
                                        <div class="control">
                                            <input
                                                class="input"
                                                type="text"
                                                name="questions[<?= htmlspecialchars($questionId) ?>][option_a]"
                                                value="<?= htmlspecialchars((string) ($options['A'] ?? '')) ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                <div class="column">
                                    <div class="field">
                                        <label class="label">Alternativa B</label>
                                        <div class="control">
                                            <input
                                                class="input"
                                                type="text"
                                                name="questions[<?= htmlspecialchars($questionId) ?>][option_b]"
                                                value="<?= htmlspecialchars((string) ($options['B'] ?? '')) ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="columns">
                                <div class="column">
                                    <div class="field">
                                        <label class="label">Alternativa C</label>
                                        <div class="control">
                                            <input
                                                class="input"
                                                type="text"
                                                name="questions[<?= htmlspecialchars($questionId) ?>][option_c]"
                                                value="<?= htmlspecialchars((string) ($options['C'] ?? '')) ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                <div class="column">
                                    <div class="field">
                                        <label class="label">Alternativa D</label>
                                        <div class="control">
                                            <input
                                                class="input"
                                                type="text"
                                                name="questions[<?= htmlspecialchars($questionId) ?>][option_d]"
                                                value="<?= htmlspecialchars((string) ($options['D'] ?? '')) ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label class="label">Resposta correta</label>
                                <div class="control">
                                    <div class="select">
                                        <select name="questions[<?= htmlspecialchars($questionId) ?>][correct_answer]" required>
                                            <?php foreach (['A', 'B', 'C', 'D'] as $answerOption): ?>
                                                <option
                                                    value="<?= $answerOption ?>"
                                                    <?= (string) $question['correct_answer'] === $answerOption ? 'selected' : '' ?>>
                                                    <?= $answerOption ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($type === 'fill_blank'): ?>
                            <div class="field">
                                <label class="label">Resposta correta</label>
                                <div class="control">
                                    <input
                                        class="input"
                                        type="text"
                                        name="questions[<?= htmlspecialchars($questionId) ?>][correct_answer]"
                                        value="<?= htmlspecialchars((string) $question['correct_answer']) ?>"
                                        required>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="notification is-warning is-light">
                                Tipo de questão não suportado:
                                <?= htmlspecialchars($type) ?>
                            </div>
                        <?php endif; ?>

                        <div class="field">
                            <label class="label">Explicação</label>
                            <div class="control">
                                <textarea
                                    class="textarea"
                                    name="questions[<?= htmlspecialchars($questionId) ?>][explanation]"><?= htmlspecialchars((string) $question['explanation']) ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="buttons mt-5">
                <button class="button is-dark" type="submit">Salvar alterações</button>
                <a class="button is-light" href="/index.php">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/layout/footer.php'; ?>