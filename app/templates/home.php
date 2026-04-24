<?php

declare(strict_types=1);

/** @var App\ParsedExam|null $parsedExam */
/** @var string|null $errorMessage */
/** @var string|null $successMessage */
/** @var string|null $uploadedFileName */
/** @var array<int, array<string, mixed>> $exams */

$exams = $exams ?? [];
?>
<?php
$pageTitle = 'Test Simulator';
include __DIR__ . '/layout/header.php';
?>

        <!-- ALERTS -->
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

        <!-- UPLOAD -->
        <div class="box">
            <h1 class="title is-4">Importar prova</h1>

            <form method="post" enctype="multipart/form-data">
                <div class="field">
                    <label class="label">Arquivo CSV</label>
                    <div class="control">
                        <input class="input" type="file" name="exam_file" accept=".csv,text/csv" required>
                    </div>
                </div>

                <button class="button is-dark">Enviar CSV</button>
            </form>
        </div>

        <!-- LISTA DE PROVAS -->
        <div class="box">
            <h2 class="title is-4">Provas disponíveis</h2>

            <?php if (empty($exams)): ?>
                <p class="has-text-grey">Nenhuma prova cadastrada ainda.</p>
            <?php else: ?>
                <div class="columns is-multiline">
                    <?php foreach ($exams as $exam): ?>
                        <div class="column is-6">
                            <div class="box">
                                <h3 class="title is-5">
                                    <a href="/exam.php?id=<?= urlencode((string) $exam['id']) ?>">
                                        <?= htmlspecialchars((string) ($exam['title'] ?? 'Prova sem título')) ?>
                                    </a>
                                </h3>

                                <p class="has-text-grey mb-3">
                                    <?= (int) ($exam['total_questions'] ?? 0) ?> questões
                                </p>

                                <div class="buttons">
                                    <a class="button is-dark is-small"
                                       href="/exam.php?id=<?= urlencode((string) $exam['id']) ?>">
                                        Estudar
                                    </a>

                                    <form method="post"
                                          onsubmit="return confirm('Deseja realmente excluir esta prova?');">
                                        <input type="hidden" name="action" value="delete_exam">
                                        <input type="hidden" name="exam_id"
                                               value="<?= htmlspecialchars((string) $exam['id']) ?>">

                                        <button class="button is-danger is-light is-small">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PREVIEW CSV -->
        <?php if ($parsedExam !== null): ?>
            <div class="box">
                <h2 class="title is-4">Resumo da prova</h2>

                <div class="columns">
                    <div class="column">
                        <div class="notification is-light">
                            <strong>Arquivo</strong><br>
                            <?= htmlspecialchars((string) $uploadedFileName) ?>
                        </div>
                    </div>

                    <div class="column">
                        <div class="notification is-light">
                            <strong>Título</strong><br>
                            <?= htmlspecialchars((string) $parsedExam->title) ?>
                        </div>
                    </div>

                    <div class="column">
                        <div class="notification is-light">
                            <strong>Questões</strong><br>
                            <?= $parsedExam->totalQuestions() ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    
        <?php include __DIR__ . '/layout/footer.php'; ?>