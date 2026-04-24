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
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #d1d5db;
            --success-bg: #ecfdf5;
            --success-text: #065f46;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --accent: #111827;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
        }

        h1,
        h2,
        h3 {
            margin-top: 0;
        }

        .muted {
            color: var(--muted);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .alert.error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .upload-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        input[type="file"] {
            max-width: 100%;
        }

        button,
        .button-link {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        button:hover,
        .button-link:hover {
            opacity: 0.92;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .meta-box {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            background: #fafafa;
        }

        .question {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 14px;
        }

        .question small {
            color: var(--muted);
            display: block;
            margin-bottom: 8px;
        }

        .options {
            margin: 10px 0 0 0;
            padding-left: 18px;
        }

        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 6px;
            display: inline-block;
            word-break: break-all;
        }

        .sample-list {
            margin: 0;
            padding-left: 18px;
        }

        .exam-link {
            color: var(--text);
            text-decoration: none;
        }

        .exam-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <h1>Test Simulator</h1>
            <p class="muted">Importe um arquivo CSV para validar e salvar uma prova no Firestore.</p>

            <?php if ($successMessage !== null): ?>
                <div class="alert success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== null): ?>
                <div class="alert error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="upload-row">
                    <input type="file" name="exam_file" accept=".csv,text/csv" required>
                    <button type="submit">Enviar CSV</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Provas disponíveis</h2>

            <?php if (empty($exams)): ?>
                <p class="muted">Nenhuma prova cadastrada ainda.</p>
            <?php else: ?>
                <?php foreach ($exams as $exam): ?>
                    <div class="question">
                        <h3>
                            <a class="exam-link" href="/exam.php?id=<?= urlencode((string) $exam['id']) ?>">
                                <?= htmlspecialchars((string) ($exam['title'] ?? 'Prova sem título')) ?>
                            </a>
                        </h3>

                        <p class="muted">
                            ID: <?= htmlspecialchars((string) ($exam['id'] ?? '')) ?>
                            · Questões: <?= (int) ($exam['total_questions'] ?? 0) ?>
                            <?php if (!empty($exam['status'])): ?>
                                · Status: <?= htmlspecialchars((string) $exam['status']) ?>
                            <?php endif; ?>
                        </p>

                        <a class="button-link" href="/exam.php?id=<?= urlencode((string) $exam['id']) ?>">
                            Estudar prova
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Formato esperado</h2>
            <p>O arquivo deve conter este cabeçalho:</p>
            <p><code>exam_title,question_id,question_type,statement,option_a,option_b,option_c,option_d,correct_answer,explanation</code></p>
            <h3>Tipos aceitos</h3>
            <ul class="sample-list">
                <li><code>multiple_choice</code></li>
                <li><code>fill_blank</code></li>
            </ul>
        </div>

        <?php if ($parsedExam !== null): ?>
            <div class="card">
                <h2>Resumo da prova importada</h2>
                <div class="meta-grid">
                    <div class="meta-box">
                        <strong>Arquivo</strong>
                        <div><?= htmlspecialchars((string) $uploadedFileName) ?></div>
                    </div>
                    <div class="meta-box">
                        <strong>Título</strong>
                        <div><?= htmlspecialchars((string) $parsedExam->title) ?></div>
                    </div>
                    <div class="meta-box">
                        <strong>Total de questões</strong>
                        <div><?= $parsedExam->totalQuestions() ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Questões importadas</h2>

                <?php foreach ($parsedExam->questions as $question): ?>
                    <div class="question">
                        <small>
                            <?= htmlspecialchars((string) $question['question_id']) ?>
                            ·
                            <?= htmlspecialchars((string) $question['question_type']) ?>
                            ·
                            ordem <?= (int) $question['order_index'] ?>
                        </small>

                        <h3><?= htmlspecialchars((string) $question['statement']) ?></h3>

                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                            <ul class="options">
                                <?php foreach ($question['options'] as $key => $value): ?>
                                    <li><strong><?= htmlspecialchars((string) $key) ?>.</strong> <?= htmlspecialchars((string) $value) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="muted">Questão de completar lacuna.</p>
                        <?php endif; ?>

                        <p><strong>Resposta correta:</strong> <?= htmlspecialchars((string) $question['correct_answer']) ?></p>

                        <?php if ((string) $question['explanation'] !== ''): ?>
                            <p><strong>Explicação:</strong> <?= htmlspecialchars((string) $question['explanation']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>