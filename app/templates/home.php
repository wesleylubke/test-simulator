<?php
declare(strict_types=1);

/** @var App\ParsedExam|null $parsedExam */
/** @var string|null $errorMessage */
/** @var string|null $successMessage */
/** @var string|null $uploadedFileName */
/** @var array<int, array<string, mixed>> $exams */

$exams = $exams ?? [];
$activeExams = count($exams);
$pageTitle = 'My Exams - Test Simulator';

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
            <h1 class="title is-3">My Exams</h1>
            <p class="subtitle is-6 has-text-grey">Select a simulator to start practicing</p>
        </div>
    </div>

    <div class="level-right">
        <button class="button app-button-dark" onclick="document.getElementById('upload-box').scrollIntoView({behavior: 'smooth'});">
            + New Exam
        </button>
    </div>
</div>

<div class="columns mb-5">
    <div class="column">
        <div class="stat-card">
            <p class="has-text-grey">Active Exams</p>
            <div class="stat-number"><?= $activeExams ?></div>
        </div>
    </div>

    <div class="column">
        <div class="stat-card">
            <p class="has-text-grey">Avg. Score</p>
            <div class="stat-number is-green">—</div>
        </div>
    </div>
</div>

<div class="field mb-5">
    <div class="control has-icons-left">
        <input id="exam-search" class="input is-medium" type="text" placeholder="Search exams...">
        <span class="icon is-left">⌕</span>
    </div>
</div>

<div class="columns is-multiline" id="exam-list">
    <?php if (empty($exams)): ?>
        <div class="column is-12">
            <div class="box app-card">
                <p class="has-text-grey">Nenhuma prova cadastrada ainda.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($exams as $exam): ?>
            <?php
            $status = strtolower((string) ($exam['status'] ?? 'published'));
            $statusClass = match ($status) {
                'draft' => 'status-draft',
                'archived' => 'status-archived',
                default => 'status-published',
            };
            ?>
            <div class="column is-12 exam-item">
                <div class="box exam-card">
                    <div class="columns is-vcentered">
                        <div class="column">
                            <div class="is-flex is-justify-content-space-between is-align-items-center mb-3">
                                <h2 class="title is-5 mb-0 exam-title">
                                    <?= htmlspecialchars((string) ($exam['title'] ?? 'Prova sem título')) ?>
                                </h2>

                                <span class="status-pill <?= $statusClass ?>">
                                    <?= htmlspecialchars((string) ($exam['status'] ?? 'Published')) ?>
                                </span>
                            </div>

                            <p class="has-text-grey is-size-7">
                                ☰ <?= (int) ($exam['total_questions'] ?? 0) ?> Questions
                                &nbsp; • &nbsp;
                                Updated recently
                            </p>
                        </div>

                        <div class="column is-narrow">
                            <div class="buttons is-right">
                                <a class="button app-button-dark"
                                   href="/exam.php?id=<?= urlencode((string) $exam['id']) ?>">
                                    Study Now
                                </a>

                                <a class="button is-light"
                                   href="/edit_exam.php?id=<?= urlencode((string) $exam['id']) ?>">
                                    ✎
                                </a>

                                <form method="post"
                                      onsubmit="return confirm('Deseja realmente excluir esta prova?');">
                                    <input type="hidden" name="action" value="delete_exam">
                                    <input type="hidden" name="exam_id"
                                           value="<?= htmlspecialchars((string) $exam['id']) ?>">

                                    <button class="button is-danger is-light" type="submit">
                                        🗑
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="upload-box" class="box app-card mt-6">
    <h2 class="title is-4">Importar nova prova</h2>
    <p class="has-text-grey mb-4">Envie um CSV no formato esperado para criar uma nova prova.</p>

    <form method="post" enctype="multipart/form-data">
        <div class="field">
            <label class="label">Arquivo CSV</label>
            <div class="control">
                <input class="input" type="file" name="exam_file" accept=".csv,text/csv" required>
            </div>
        </div>

        <button class="button app-button-dark" type="submit">Enviar CSV</button>
    </form>
</div>

<div class="box app-card mt-5">
    <h2 class="title is-5">Formato esperado</h2>
    <pre><code>exam_title,question_id,question_type,statement,option_a,option_b,option_c,option_d,correct_answer,explanation</code></pre>
</div>

<script>
document.getElementById('exam-search')?.addEventListener('input', function () {
    const term = this.value.toLowerCase();

    document.querySelectorAll('.exam-item').forEach(function (item) {
        const title = item.querySelector('.exam-title')?.innerText.toLowerCase() || '';
        item.style.display = title.includes(term) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>