<?php
declare(strict_types=1);

/** @var array $exams */
/** @var array $folders */

$pageTitle = 'Minhas provas - Test Simulator';

include __DIR__ . '/layout/header.php';

$groupedExams = [];

foreach ($exams as $exam) {
    $folderName = $exam['folder_name'] ?: 'Sem pasta';
    $groupedExams[$folderName][] = $exam;
}
?>

<header class="app-page-header">
    <div>
        <h1 class="app-page-title">Minhas provas</h1>
        <p class="app-page-subtitle">Organize suas provas por assunto.</p>
    </div>
</header>

<?php if ($successMessage): ?>
    <div class="notification is-success is-light"><?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="notification is-danger is-light"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<!-- CRIAR PASTA -->
<section class="app-panel p-5 mb-5">
    <h2 class="title is-5">Nova pasta</h2>

    <form method="post" class="columns is-vcentered">
        <input type="hidden" name="action" value="create_folder">

        <div class="column">
            <input
                class="input"
                type="text"
                name="folder_name"
                placeholder="Ex: Biologia"
                required
            >
        </div>

        <div class="column is-narrow">
            <button class="button app-button-primary" type="submit">
                Criar
            </button>
        </div>
    </form>
</section>

<!-- LISTA DE PASTAS -->
<?php if (empty($groupedExams)): ?>
    <div class="app-panel p-5 has-text-centered">
        <p>Nenhuma prova cadastrada.</p>
    </div>
<?php else: ?>

    <?php foreach ($groupedExams as $folderName => $folderExams): ?>
    <?php
    $folderKey = 'folder_' . md5((string) $folderName);
    ?>

    <section class="mb-5 app-panel p-4">
        <button
            type="button"
            class="button is-fullwidth is-justify-content-space-between folder-toggle"
            data-target="<?= htmlspecialchars($folderKey) ?>"
            aria-expanded="true"
        >
            <span>
                <strong><?= htmlspecialchars($folderName) ?></strong>
                <span class="tag is-dark ml-2"><?= count($folderExams) ?> provas</span>
            </span>

            <span class="folder-arrow">▼</span>
        </button>

        <div id="<?= htmlspecialchars($folderKey) ?>" class="folder-content mt-4">
            <?php foreach ($folderExams as $exam): ?>
                <?php $examId = (string) $exam['id']; ?>

                <div class="app-exam-card">
                    <div class="columns is-vcentered">
                        <div class="column">
                            <h3 class="app-exam-title">
                                <?= htmlspecialchars((string) $exam['title']) ?>
                            </h3>

                            <p class="app-exam-meta">
                                <?= (int) ($exam['total_questions'] ?? 0) ?> questões
                            </p>
                        </div>

                        <div class="column is-narrow">
                            <div class="buttons">
                                <a class="button app-button-primary"
                                   href="/exam.php?id=<?= urlencode($examId) ?>">
                                    Estudar
                                </a>

                                <a class="button is-light"
                                   href="/edit_exam.php?id=<?= urlencode($examId) ?>">
                                    Editar
                                </a>

                                <form method="post"
                                      onsubmit="return confirm('Excluir esta prova?');">
                                    <input type="hidden" name="action" value="delete_exam">
                                    <input type="hidden" name="exam_id" value="<?= htmlspecialchars($examId) ?>">

                                    <button class="button is-danger is-light">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php endif; ?>

<!-- IMPORTAR CSV -->
<section class="app-panel p-5 mt-6">
    <h2 class="title is-5">Importar prova</h2>

    <form method="post" enctype="multipart/form-data">
        <div class="field">
            <input class="input" type="file" name="exam_file" required>
        </div>

        <button class="button app-button-primary">
            Enviar CSV
        </button>
    </form>
</section>

<script>
document.querySelectorAll('.folder-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
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
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>