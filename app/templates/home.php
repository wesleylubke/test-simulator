<?php

declare(strict_types=1);

/** @var App\ParsedExam|null $parsedExam */
/** @var string|null $errorMessage */
/** @var string|null $successMessage */
/** @var string|null $uploadedFileName */
/** @var array<int, array<string, mixed>> $exams */

$exams = $exams ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Simulator</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
</head>

<body>
<section class="section">
    <div class="container">
        <div class="box">
            <h1 class="title">Test Simulator</h1>
            <p class="subtitle">Importe um arquivo CSV para validar e salvar uma prova no Firestore.</p>

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

            <form method="post" enctype="multipart/form-data">
                <div class="field">
                    <label class="label">Arquivo CSV</label>
                    <div class="control">
                        <input class="input" type="file" name="exam_file" accept=".csv,text/csv" required>
                    </div>
                </div>

                <button class="button is-dark" type="submit">Enviar CSV</button>
            </form>
        </div>

        <div class="box">
            <h2 class="title is-4">Provas disponíveis</h2>

            <?php if (empty($exams)): ?>
                <p class="has-text-grey">Nenhuma prova cadastrada ainda.</p>
            <?php else: ?>
                <?php foreach ($exams as $exam): ?>
                    <div class="box">
                        <h3 class="title is-5">
                            <a href="/exam.php?id=<?= urlencode((string) $exam['id']) ?>">
                                <?= htmlspecialchars((string) ($exam['title'] ?? 'Prova sem título')) ?>
                            </a>
                        </h3>

                        <p class="has-text-grey mb-3">
                            ID: <?= htmlspecialchars((string) ($exam['id'] ?? '')) ?>
                            · Questões: <?= (int) ($exam['total_questions'] ?? 0) ?>
                            <?php if (!empty($exam['status'])): ?>
                                · Status: <?= htmlspecialchars((string) $exam['status']) ?>
                            <?php endif; ?>
                        </p>

                        <div class="buttons">
                            <a class="button is-dark" href="/exam.php?id=<?= urlencode((string) $exam['id']) ?>">
                                Estudar prova
                            </a>

                            <form method="post" onsubmit="return confirm('Tem certeza que deseja excluir esta prova? Esta ação não pode ser desfeita.');">
                                <input type="hidden" name="action" value="delete_exam">
                                <input type="hidden" name="exam_id" value="<?= htmlspecialchars((string) $exam['id']) ?>">
                                <button class="button is-danger is-light" type="submit">
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="box">
            <h2 class="title is-4">Formato esperado</h2>
            <p>O arquivo deve conter este cabeçalho:</p>

            <pre><code>exam_title,question_id,question_type,statement,option_a,option_b,option_c,option_d,correct_answer,explanation</code></pre>

            <h3 class="title is-5">Tipos aceitos</h3>
            <ul>
                <li><code>multiple_choice</code></li>
                <li><code>fill_blank</code></li>
            </ul>
        </div>

        <?php if ($parsedExam !== null): ?>
            <div class="box">
                <h2 class="title is-4">Resumo da prova importada</h2>

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
                            <strong>Total de questões</strong><br>
                            <?= $parsedExam->totalQuestions() ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box">
                <h2 class="title is-4">Questões importadas</h2>

                <?php foreach ($parsedExam->questions as $question): ?>
                    <div class="box">
                        <p class="is-size-7 has-text-grey">
                            <?= htmlspecialchars((string) $question['question_id']) ?>
                            ·
                            <?= htmlspecialchars((string) $question['question_type']) ?>
                            ·
                            ordem <?= (int) $question['order_index'] ?>
                        </p>

                        <h3 class="title is-5">
                            <?= htmlspecialchars((string) $question['statement']) ?>
                        </h3>

                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                            <div class="content">
                                <ul>
                                    <?php foreach ($question['options'] as $key => $value): ?>
                                        <li>
                                            <strong><?= htmlspecialchars((string) $key) ?>.</strong>
                                            <?= htmlspecialchars((string) $value) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <p class="has-text-grey">Questão de completar lacuna.</p>
                        <?php endif; ?>

                        <p>
                            <strong>Resposta correta:</strong>
                            <?= htmlspecialchars((string) $question['correct_answer']) ?>
                        </p>

                        <?php if ((string) $question['explanation'] !== ''): ?>
                            <p>
                                <strong>Explicação:</strong>
                                <?= htmlspecialchars((string) $question['explanation']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
</body>

</html>