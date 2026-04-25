<?php
declare(strict_types=1);

/** @var App\ParsedExam|null $parsedExam */
/** @var string|null $errorMessage */
/** @var string|null $successMessage */
/** @var string|null $uploadedFileName */
/** @var array<int, array<string, mixed>> $exams */
/** @var array<int, array<string, mixed>> $folders */

$exams = $exams ?? [];
$folders = $folders ?? [];

$pageTitle = 'Provas - Test Simulator';
$pageHeader = 'Provas';

$foldersById = [];

foreach ($folders as $folder) {
    $foldersById[(string) $folder['id']] = $folder;
}

$groupedExams = [];

foreach ($exams as $exam) {
    $folderId = (string) ($exam['folder_id'] ?? '');

    $folderName = $folderId !== '' && isset($foldersById[$folderId])
        ? (string) $foldersById[$folderId]['name']
        : (string) ($exam['folder_name'] ?? 'Sem pasta');

    if ($folderId === '') {
        $folderId = 'no-folder';
        $folderName = 'Sem pasta';
    }

    if (!isset($groupedExams[$folderId])) {
        $groupedExams[$folderId] = [
            'id' => $folderId,
            'name' => $folderName,
            'exams' => [],
        ];
    }

    $groupedExams[$folderId]['exams'][] = $exam;
}

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

<header class="app-page-header">
    <div>
        <h1 class="app-page-title">Minhas provas</h1>
        <p class="app-page-subtitle">Organize suas provas em pastas e estude com praticidade.</p>
    </div>

    <button
        class="button app-button-primary"
        type="button"
        onclick="document.getElementById('upload-panel').scrollIntoView({behavior: 'smooth'});"
    >
        + Nova prova
    </button>
</header>

<div class="columns mb-5">
    <div class="column">
        <div class="app-panel app-stat">
            <div class="app-stat-icon">📁</div>
            <div>
                <div class="app-stat-label">Pastas</div>
                <div class="app-stat-value"><?= count($folders) ?></div>
            </div>
        </div>
    </div>

    <div class="column">
        <div class="app-panel app-stat">
            <div class="app-stat-icon">✓</div>
            <div>
                <div class="app-stat-label">Provas</div>
                <div class="app-stat-value"><?= count($exams) ?></div>
            </div>
        </div>
    </div>

    <div class="column">
        <div class="app-panel app-stat">
            <div class="app-stat-icon">↗</div>
            <div>
                <div class="app-stat-label">Média geral</div>
                <div class="app-stat-value is-success">—</div>
            </div>
        </div>
    </div>
</div>

<section class="app-panel mb-5">
    <div class="columns is-vcentered">
        <div class="column">
            <h2 class="title is-5 mb-2">Nova pasta</h2>
            <p class="has-text-grey">Crie uma pasta para agrupar provas por assunto.</p>
        </div>

        <div class="column is-two-thirds">
            <form method="post" class="columns is-vcentered">
                <input type="hidden" name="action" value="create_folder">

                <div class="column">
                    <input
                        class="input"
                        type="text"
                        name="folder_name"
                        placeholder="Ex: Biologia, História, Matemática"
                        required
                    >
                </div>

                <div class="column is-narrow">
                    <button class="button app-button-primary" type="submit">
                        Criar pasta
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<div class="field mb-5">
    <div class="control">
        <input
            id="exam-search"
            class="input app-search"
            type="text"
            placeholder="Buscar provas..."
            autocomplete="off"
        >
    </div>
</div>

<section id="exam-list" class="mb-6">
    <?php if (empty($groupedExams)): ?>
        <div class="app-panel has-text-centered">
            <h2 class="title is-5">Nenhuma prova cadastrada</h2>
            <p class="has-text-grey mb-4">Importe um CSV para começar.</p>
            <button
                class="button app-button-primary"
                type="button"
                onclick="document.getElementById('upload-panel').scrollIntoView({behavior: 'smooth'});"
            >
                Importar primeira prova
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($groupedExams as $folder): ?>
            <?php
            $folderId = (string) $folder['id'];
            $folderName = (string) $folder['name'];
            $folderExams = (array) $folder['exams'];
            $folderKey = 'folder_' . md5($folderId);
            ?>

            <section class="app-panel folder-group">
                <div class="folder-header">
                    <button
                        type="button"
                        class="folder-toggle"
                        data-target="<?= htmlspecialchars($folderKey) ?>"
                        aria-expanded="false"
                    >
                        <span>
                            <strong><?= htmlspecialchars($folderName) ?></strong>
                            <span class="tag is-dark ml-2"><?= count($folderExams) ?> provas</span>
                        </span>

                        <span class="folder-arrow">▶</span>
                    </button>

                    <?php if ($folderId !== 'no-folder'): ?>
                        <div class="folder-actions">
                            <button
                                type="button"
                                class="app-button-icon"
                                title="Editar pasta"
                                onclick="document.getElementById('edit-folder-<?= htmlspecialchars($folderId) ?>').classList.toggle('is-hidden')"
                            >
                                ✎
                            </button>

                            <form
                                method="post"
                                class="folder-action-form"
                                onsubmit="return confirm('Excluir esta pasta? As provas ficarão sem pasta.');"
                            >
                                <input type="hidden" name="action" value="delete_folder">
                                <input type="hidden" name="folder_id" value="<?= htmlspecialchars($folderId) ?>">

                                <button class="app-button-icon is-danger" type="submit" title="Excluir pasta">
                                    🗑
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($folderId !== 'no-folder'): ?>
                    <form method="post" id="edit-folder-<?= htmlspecialchars($folderId) ?>" class="is-hidden mt-4">
                        <input type="hidden" name="action" value="update_folder">
                        <input type="hidden" name="folder_id" value="<?= htmlspecialchars($folderId) ?>">

                        <div class="columns is-vcentered">
                            <div class="column">
                                <input
                                    class="input"
                                    type="text"
                                    name="folder_name"
                                    value="<?= htmlspecialchars($folderName) ?>"
                                    required
                                >
                            </div>

                            <div class="column is-narrow">
                                <button class="button app-button-primary" type="submit">
                                    Salvar
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <div id="<?= htmlspecialchars($folderKey) ?>" class="folder-content mt-4" style="display: none;">
                    <?php foreach ($folderExams as $exam): ?>
                        <?php
                        $examId = (string) ($exam['id'] ?? '');
                        $title = (string) ($exam['title'] ?? 'Prova sem título');
                        $status = strtolower((string) ($exam['status'] ?? 'processed'));

                        $statusClass = match ($status) {
                            'draft' => 'is-draft',
                            'archived' => 'is-archived',
                            default => 'is-processed',
                        };

                        $statusLabel = match ($status) {
                            'draft' => 'Rascunho',
                            'archived' => 'Arquivada',
                            default => 'Processada',
                        };
                        ?>

                        <article class="app-exam-card exam-item" data-title="<?= htmlspecialchars(mb_strtolower($title)) ?>">
                            <div class="columns is-vcentered">
                                <div class="column">
                                    <h3 class="app-exam-title"><?= htmlspecialchars($title) ?></h3>

                                    <p class="app-exam-meta">
                                        ☰ <?= (int) ($exam['total_questions'] ?? 0) ?> questões
                                    </p>
                                </div>

                                <div class="column is-narrow">
                                    <div class="buttons app-exam-actions">
                                        <span class="app-status <?= htmlspecialchars($statusClass) ?>">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>

                                        <a class="button app-button-primary" href="/exam.php?id=<?= urlencode($examId) ?>">
                                            Estudar
                                        </a>

                                        <a class="app-button-icon" href="/edit_exam.php?id=<?= urlencode($examId) ?>" title="Editar prova">
                                            ✎
                                        </a>

                                        <form method="post" class="folder-action-form" onsubmit="return confirm('Excluir esta prova?');">
                                            <input type="hidden" name="action" value="delete_exam">
                                            <input type="hidden" name="exam_id" value="<?= htmlspecialchars($examId) ?>">

                                            <button class="app-button-icon is-danger" type="submit" title="Excluir prova">
                                                🗑
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section id="upload-panel" class="app-panel app-upload mb-5">
    <h2 class="title is-4">Importar prova CSV</h2>
    <p class="has-text-grey mb-4">Envie um arquivo CSV no formato esperado. A prova será validada e salva no Firestore.</p>

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
</section>

<?php if ($parsedExam !== null): ?>
    <section class="app-panel mb-5">
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
    </section>
<?php endif; ?>

<section class="app-panel">
    <h2 class="title is-5">Formato esperado</h2>
    <pre><code>exam_title,question_id,question_type,statement,option_a,option_b,option_c,option_d,correct_answer,explanation</code></pre>
</section>

<script>
document.querySelectorAll('.folder-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const targetId = button.dataset.target;
        const content = document.getElementById(targetId);
        const arrow = button.querySelector('.folder-arrow');

        if (!content) return;

        const isHidden = content.style.display === 'none';

        content.style.display = isHidden ? '' : 'none';
        button.setAttribute('aria-expanded', isHidden ? 'true' : 'false');

        if (arrow) {
            arrow.textContent = isHidden ? '▼' : '▶';
        }
    });
});

const searchInput = document.getElementById('exam-search');

if (searchInput) {
    searchInput.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();

        document.querySelectorAll('.exam-item').forEach(function (item) {
            const title = item.dataset.title || '';
            item.style.display = title.includes(term) ? '' : 'none';
        });

        document.querySelectorAll('.folder-group').forEach(function (folder) {
            const visibleItems = folder.querySelectorAll('.exam-item:not([style*="display: none"])');
            folder.style.display = visibleItems.length > 0 || term === '' ? '' : 'none';
        });
    });
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>