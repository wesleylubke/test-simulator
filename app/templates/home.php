<?php

declare(strict_types=1);

/** @var App\ParsedExam|null $parsedExam */
/** @var string|null $errorMessage */
/** @var string|null $successMessage */
/** @var string|null $uploadedFileName */
/** @var array<int, array<string, mixed>> $exams */

$exams = $exams ?? [];
$pageTitle = 'Minhas provas - Test Simulator';

$activeExams = count($exams);
$avgScore = '—';

include __DIR__ . '/layout/header.php';
?>

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

<div class="level mb-5">
    <div class="level-left">
        <div>
            <h1 class="title is-2 app-page-title">Minhas provas</h1>
            <p class="app-subtitle">Selecione uma prova para estudar ou importe um novo CSV.</p>
        </div>
    </div>

    <div class="level-right desktop-actions">
        <button
            class="button app-button-primary"
            type="button"
            onclick="document.getElementById('upload-panel').scrollIntoView({behavior: 'smooth'});"
        >
            + Nova prova
        </button>
    </div>
</div>

<div class="columns mb-5">
    <div class="column">
        <div class="stat-card">
            <div class="stat-label">Provas ativas</div>
            <div class="stat-value"><?= (int) $activeExams ?></div>
        </div>
    </div>

    <div class="column">
        <div class="stat-card">
            <div class="stat-label">Média geral</div>
            <div class="stat-value is-success-text"><?= htmlspecialchars((string) $avgScore) ?></div>
        </div>
    </div>
</div>

<div class="field mb-5">
    <div class="control has-icons-left">
        <input
            id="exam-search"
            class="input search-input"
            type="text"
            placeholder="Buscar provas..."
            autocomplete="off"
        >
        <span class="icon is-left">⌕</span>
    </div>
</div>

<div id="exam-list">
    <?php if (empty($exams)): ?>
        <div class="app-card p-5 has-text-centered">
            <h2 class="title is-5">Nenhuma prova cadastrada</h2>
            <p class="has-text-grey mb-4">Importe um CSV para começar a estudar.</p>
            <button
                class="button app-button-primary"
                type="button"
                onclick="document.getElementById('upload-panel').scrollIntoView({behavior: 'smooth'});"
            >
                Importar primeira prova
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($exams as $exam): ?>
            <?php
            $examId = (string) ($exam['id'] ?? '');
            $title = (string) ($exam['title'] ?? 'Prova sem título');
            $status = strtolower((string) ($exam['status'] ?? 'processed'));

            $statusClass = match ($status) {
                'draft' => 'status-draft',
                'archived' => 'status-archived',
                'processed' => 'status-processed',
                default => 'status-published',
            };

            $statusLabel = match ($status) {
                'draft' => 'Rascunho',
                'archived' => 'Arquivada',
                'processed' => 'Publicada',
                default => ucfirst($status),
            };
            ?>

            <article class="exam-card mb-4 exam-item" data-title="<?= htmlspecialchars(mb_strtolower($title)) ?>">
                <div class="columns is-vcentered is-variable is-5">
                    <div class="column">
                        <div class="is-flex is-justify-content-space-between is-align-items-start mb-3">
                            <h2 class="title is-5 exam-title mb-0">
                                <?= htmlspecialchars($title) ?>
                            </h2>

                            <span class="status-pill <?= htmlspecialchars($statusClass) ?>">
                                <?= htmlspecialchars($statusLabel) ?>
                            </span>
                        </div>

                        <p class="exam-meta">
                            ☰ <?= (int) ($exam['total_questions'] ?? 0) ?> questões
                            <span class="mx-2">•</span>
                            Atualizada recentemente
                        </p>
                    </div>

                    <div class="column is-narrow">
                        <div class="buttons exam-actions">
                            <a class="button app-button-primary" href="/exam.php?id=<?= urlencode($examId) ?>">
                                Estudar
                            </a>

                            <a class="app-icon-button" href="/edit_exam.php?id=<?= urlencode($examId) ?>" title="Editar">
                                ✎
                            </a>

                            <form method="post" onsubmit="return confirm('Deseja realmente excluir esta prova?');">
                                <input type="hidden" name="action" value="delete_exam">
                                <input type="hidden" name="exam_id" value="<?= htmlspecialchars($examId) ?>">

                                <button class="app-icon-button is-danger" type="submit" title="Excluir">
                                    🗑
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="upload-panel" class="app-card upload-panel p-5 mt-6">
    <h2 class="title is-4">Importar nova prova</h2>
    <p class="has-text-grey mb-4">Envie um CSV no formato esperado. A prova será validada e salva no Firestore.</p>

    <form method="post" enctype="multipart/form-data">
        <div class="field">
            <label class="label">Arquivo CSV</label>
            <div class="control">
                <input class="input" type="file" name="exam_file" accept=".csv,text/csv" required>
            </div>
        </div>

        <button class="button app-button-primary" type="submit">
            Enviar CSV
        </button>
    </form>
</div>

<?php if ($parsedExam !== null): ?>
    <div class="app-card p-5 mt-5">
        <h2 class="title is-4">Prova importada</h2>

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

<div class="app-card p-5 mt-5">
    <h2 class="title is-5">Formato esperado</h2>
    <pre><code>exam_title,question_id,question_type,statement,option_a,option_b,option_c,option_d,correct_answer,explanation</code></pre>
</div>

<script>
const searchInput = document.getElementById('exam-search');

if (searchInput) {
    searchInput.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();

        document.querySelectorAll('.exam-item').forEach(function (item) {
            const title = item.dataset.title || '';
            item.style.display = title.includes(term) ? '' : 'none';
        });
    });
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>