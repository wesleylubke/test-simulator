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

$pageTitle = 'Minhas provas - Test Simulator';

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
            'color' => $folderId !== 'no-folder' && isset($foldersById[$folderId])
                ? (string) ($foldersById[$folderId]['color'] ?? 'blue')
                : 'blue',
            'exams' => [],
        ];
    }

    $groupedExams[$folderId]['exams'][] = $exam;
}

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

<header class="app-page-header">
    <div>
        <h1 class="app-page-title">Provas</h1>
        <p class="app-page-subtitle">Organize suas provas por assunto e selecione uma pasta para estudar.</p>
    </div>

    <button
        class="button app-button-primary"
        type="button"
        onclick="document.getElementById('upload-panel').scrollIntoView({behavior: 'smooth'});">
        + Nova prova
    </button>
</header>

<div class="columns mb-5">
    <div class="column">
        <div class="app-panel app-stat">
            <div class="app-stat-label">Provas cadastradas</div>
            <div class="app-stat-value"><?= (int) $activeExams ?></div>
        </div>
    </div>

    <div class="column">
        <div class="app-panel app-stat">
            <div class="app-stat-label">Pastas</div>
            <div class="app-stat-value"><?= count($folders) ?></div>
        </div>
    </div>

    <div class="column">
        <div class="app-panel app-stat">
            <div class="app-stat-label">Média geral</div>
            <div class="app-stat-value is-success"><?= htmlspecialchars((string) $avgScore) ?></div>
        </div>
    </div>
</div>

<section class="app-panel p-5 mb-5">
    <h2 class="title is-5">Criar nova pasta</h2>

    <form method="post" class="columns is-vcentered">
        <input type="hidden" name="action" value="create_folder">

        <div class="column">
            <input
                class="input"
                type="text"
                name="folder_name"
                placeholder="Ex: Biologia, História, Matemática"
                required>
        </div>

        <div class="column is-narrow">
            <button class="button app-button-primary" type="submit">
                Criar pasta
            </button>
        </div>
    </form>
</section>

<div class="field mb-5">
    <div class="control has-icons-left">
        <input
            id="exam-search"
            class="input app-search"
            type="text"
            placeholder="Buscar provas..."
            autocomplete="off">
        <span class="icon is-left">⌕</span>
    </div>
</div>

<section id="exam-list" class="mb-6">
    <?php if (empty($groupedExams)): ?>
        <div class="app-panel p-5 has-text-centered">
            <h2 class="title is-5">Nenhuma prova cadastrada</h2>
            <p class="has-text-grey mb-4">Importe um CSV para começar a estudar.</p>

            <button
                class="button app-button-primary"
                type="button"
                onclick="document.getElementById('upload-panel').scrollIntoView({behavior: 'smooth'});">
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

            <section class="app-panel p-4 mb-5 folder-group">
                <div class="is-flex is-justify-content-space-between is-align-items-center mb-3">
                    <button
                        type="button"
                        class="button is-fullwidth is-justify-content-space-between folder-toggle"
                        data-target="<?= htmlspecialchars($folderKey) ?>"
                        aria-expanded="false">
                        <span>
                            <strong><?= htmlspecialchars($folderName) ?></strong>
                            <span class="tag is-dark ml-2"><?= count($folderExams) ?> provas</span>
                        </span>

                        <span class="folder-arrow">▶</span>
                    </button>

                    <?php if ($folderId !== 'no-folder'): ?>
                        <div class="buttons ml-3">
                            <button
                                type="button"
                                class="button is-small is-light"
                                onclick="document.getElementById('edit-folder-<?= htmlspecialchars($folderId) ?>').classList.toggle('is-hidden')">
                                Editar
                            </button>

                            <form method="post" onsubmit="return confirm('Excluir esta pasta? As provas ficarão sem pasta.');">
                                <input type="hidden" name="action" value="delete_folder">
                                <input type="hidden" name="folder_id" value="<?= htmlspecialchars($folderId) ?>">

                                <button class="button is-small is-danger is-light" type="submit">
                                    Excluir
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($folderId !== 'no-folder'): ?>
                    <form method="post" id="edit-folder-<?= htmlspecialchars($folderId) ?>" class="is-hidden mb-4">
                        <input type="hidden" name="action" value="update_folder">
                        <input type="hidden" name="folder_id" value="<?= htmlspecialchars($folderId) ?>">

                        <div class="columns is-vcentered">
                            <div class="column">
                                <input
                                    class="input"
                                    type="text"
                                    name="folder_name"
                                    value="<?= htmlspecialchars($folderName) ?>"
                                    required>
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
                            'processed' => 'is-processed',
                            default => 'is-published',
                        };

                        $statusLabel = match ($status) {
                            'draft' => 'Rascunho',
                            'archived' => 'Arquivada',
                            default => 'Publicada',
                        };
                        ?>

                        <article class="app-exam-card exam-item" data-title="<?= htmlspecialchars(mb_strtolower($title)) ?>">
                            <div class="columns is-vcentered">
                                <div class="column">
                                    <div class="is-flex is-justify-content-space-between is-align-items-start mb-2">
                                        <h3 class="app-exam-title">
                                            <?= htmlspecialchars($title) ?>
                                        </h3>

                                        <span class="app-status <?= htmlspecialchars($statusClass) ?>">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>
                                    </div>

                                    <p class="app-exam-meta">
                                        <?= (int) ($exam['total_questions'] ?? 0) ?> questões
                                    </p>
                                </div>

                                <div class="column is-narrow">
                                    <div class="buttons app-exam-actions">
                                        <a class="button app-button-primary" href="/exam.php?id=<?= urlencode($examId) ?>">
                                            Estudar
                                        </a>

                                        <a class="button is-light" href="/edit_exam.php?id=<?= urlencode($examId) ?>">
                                            Editar
                                        </a>

                                        <form method="post" onsubmit="return confirm('Excluir esta prova?');">
                                            <input type="hidden" name="action" value="delete_exam">
                                            <input type="hidden" name="exam_id" value="<?= htmlspecialchars($examId) ?>">

                                            <button class="button is-danger is-light" type="submit">
                                                Excluir
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
</section>

<?php if ($parsedExam !== null): ?>
    <section class="app-panel p-5 mb-5">
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

<section class="app-panel p-5">
    <h2 class="title is-5">Formato esperado</h2>
    <pre><code>exam_title,question_id,question_type,statement,option_a,option_b,option_c,option_d,correct_answer,explanation</code></pre>
</section>

<script>
    document.querySelectorAll('.folder-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = button.dataset.target;
            const content = document.getElementById(targetId);
            const arrow = button.querySelector('.folder-arrow');

            if (!content) {
                return;
            }

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
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();

            document.querySelectorAll('.exam-item').forEach(function(item) {
                const title = item.dataset.title || '';
                item.style.display = title.includes(term) ? '' : 'none';
            });

            document.querySelectorAll('.folder-group').forEach(function(folder) {
                const visibleItems = folder.querySelectorAll('.exam-item:not([style*="display: none"])');
                folder.style.display = visibleItems.length > 0 || term === '' ? '' : 'none';
            });
        });
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>