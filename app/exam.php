<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\AuthService;

AuthService::requireLogin();

use App\GoogleAccessTokenService;
use App\FirestoreRestRepository;

$examId = trim((string) ($_GET['id'] ?? ''));
$errorMessage = null;
$exam = null;
$questions = [];
$results = [];
$totalCorrect = 0;
$totalWrong = 0;
$attemptId = null;
$attemptMessage = null;

function firestoreValueToPhp(array $value): mixed
{
    if (isset($value['stringValue'])) return $value['stringValue'];
    if (isset($value['integerValue'])) return (int) $value['integerValue'];
    if (isset($value['doubleValue'])) return (float) $value['doubleValue'];
    if (isset($value['booleanValue'])) return (bool) $value['booleanValue'];
    if (isset($value['timestampValue'])) return $value['timestampValue'];

    if (isset($value['mapValue'])) {
        $result = [];
        foreach (($value['mapValue']['fields'] ?? []) as $key => $fieldValue) {
            $result[$key] = firestoreValueToPhp($fieldValue);
        }
        return $result;
    }

    if (isset($value['arrayValue'])) {
        return array_map('firestoreValueToPhp', $value['arrayValue']['values'] ?? []);
    }

    return null;
}

function firestoreRequest(string $url): array
{
    $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

    if (!is_string($credentialsPath) || $credentialsPath === '') {
        throw new RuntimeException('Variável GOOGLE_APPLICATION_CREDENTIALS não configurada.');
    }

    $token = (new GoogleAccessTokenService($credentialsPath))->getAccessToken();

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Erro CURL: ' . curl_error($ch));
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $decoded = json_decode($response, true);

    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException(
            'Erro Firestore REST (' . $statusCode . '): ' . json_encode($decoded, JSON_UNESCAPED_UNICODE)
        );
    }

    return is_array($decoded) ? $decoded : [];
}

try {
    if ($examId === '') {
        throw new RuntimeException('ID da prova não informado.');
    }

    $projectId = getenv('GOOGLE_CLOUD_PROJECT');

    if (!is_string($projectId) || $projectId === '') {
        throw new RuntimeException('Variável GOOGLE_CLOUD_PROJECT não configurada.');
    }

    $baseUrl = sprintf(
        'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents',
        rawurlencode($projectId)
    );

    $examResponse = firestoreRequest($baseUrl . '/exams/' . rawurlencode($examId));
    $examFields = $examResponse['fields'] ?? [];

    $exam = [
        'id' => $examId,
        'title' => firestoreValueToPhp($examFields['title'] ?? []) ?? 'Prova sem título',
        'total_questions' => firestoreValueToPhp($examFields['total_questions'] ?? []) ?? 0,
        'status' => firestoreValueToPhp($examFields['status'] ?? []) ?? '',
    ];

    $questionsResponse = firestoreRequest(
        $baseUrl . '/exams/' . rawurlencode($examId) . '/questions?pageSize=200'
    );

    foreach (($questionsResponse['documents'] ?? []) as $document) {
        $fields = $document['fields'] ?? [];
        $nameParts = explode('/', (string) ($document['name'] ?? ''));
        $documentId = end($nameParts);

        $questions[] = [
            'id' => $documentId,
            'question_id' => firestoreValueToPhp($fields['question_id'] ?? []) ?? $documentId,
            'statement' => firestoreValueToPhp($fields['statement'] ?? []) ?? '',
            'type' => firestoreValueToPhp($fields['type'] ?? []) ?? '',
            'options' => firestoreValueToPhp($fields['options'] ?? []) ?? [],
            'correct_answer' => firestoreValueToPhp($fields['correct_answer'] ?? []) ?? '',
            'explanation' => firestoreValueToPhp($fields['explanation'] ?? []) ?? '',
            'order_index' => firestoreValueToPhp($fields['order_index'] ?? []) ?? 0,
        ];
    }

    usort(
        $questions,
        static fn(array $a, array $b): int => ((int) $a['order_index']) <=> ((int) $b['order_index'])
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($questions as $index => $question) {
            $userAnswer = trim((string) ($_POST['answer_' . $index] ?? ''));
            $correctAnswer = trim((string) $question['correct_answer']);

            $isCorrect = mb_strtolower($userAnswer) === mb_strtolower($correctAnswer);

            $isCorrect ? $totalCorrect++ : $totalWrong++;

            $results[$index] = [
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
            ];
        }

        $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

        if (!is_string($credentialsPath) || $credentialsPath === '') {
            throw new RuntimeException('Variável GOOGLE_APPLICATION_CREDENTIALS não configurada.');
        }

        $tokenService = new GoogleAccessTokenService($credentialsPath);
        $repository = new FirestoreRestRepository($tokenService);

        $answersToSave = [];

        foreach ($questions as $index => $question) {
            $answersToSave[(string) $question['question_id']] = [
                'question_id' => (string) $question['question_id'],
                'user_answer' => (string) ($results[$index]['user_answer'] ?? ''),
                'correct_answer' => (string) ($results[$index]['correct_answer'] ?? ''),
                'is_correct' => (bool) ($results[$index]['is_correct'] ?? false),
            ];
        }

        $attemptId = $repository->saveAttempt(
            $examId,
            (string) $exam['title'],
            count($questions),
            $totalCorrect,
            $totalWrong,
            $answersToSave
        );

        $attemptMessage = "Tentativa salva com sucesso. ID: {$attemptId}";
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}
?>

<?php
$pageTitle = (string) ($exam['title'] ?? 'Prova') . ' - Test Simulator';
include __DIR__ . '/templates/layout/header.php';
?>

<div class="mb-5">
    <a class="button is-light" href="/index.php">← Voltar para provas</a>
</div>

<?php if ($errorMessage !== null): ?>
    <div class="notification is-danger is-light">
        <?= htmlspecialchars($errorMessage) ?>
    </div>
<?php elseif ($exam !== null): ?>

    <div class="app-question-card">
        <h1 class="title is-3"><?= htmlspecialchars((string) $exam['title']) ?></h1>
        <p class="subtitle is-6 has-text-grey">
            ID: <?= htmlspecialchars((string) $exam['id']) ?>
            · Questões: <?= count($questions) ?>
            <?php if ((string) $exam['status'] !== ''): ?>
                · Status: <?= htmlspecialchars((string) $exam['status']) ?>
            <?php endif; ?>
        </p>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="columns mt-4">
                <div class="column">
                    <div class="notification is-success is-light">
                        <strong>Acertos</strong><br>
                        <?= (int) $totalCorrect ?>
                    </div>
                </div>

                <div class="column">
                    <div class="notification is-danger is-light">
                        <strong>Erros</strong><br>
                        <?= (int) $totalWrong ?>
                    </div>
                </div>

                <div class="column">
                    <div class="notification is-light">
                        <strong>Total</strong><br>
                        <?= count($questions) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($attemptMessage !== null): ?>
            <div class="notification is-info is-light mt-4">
                <?= htmlspecialchars($attemptMessage) ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($questions)): ?>
        <div class="notification is-warning is-light">
            Nenhuma questão encontrada para esta prova.
        </div>
    <?php else: ?>
        <form method="post">
            <?php foreach ($questions as $index => $question): ?>
                <?php
                $fieldName = 'answer_' . $index;
                $previousAnswer = (string) ($_POST[$fieldName] ?? '');
                ?>

                <div class="app-question-card">
                    <p class="is-size-7 has-text-grey mb-2">
                        Questão <?= $index + 1 ?>
                        · <?= htmlspecialchars((string) $question['question_id']) ?>
                        · <?= htmlspecialchars((string) $question['type']) ?>
                    </p>

                    <h2 class="title is-5">
                        <?= htmlspecialchars((string) $question['statement']) ?>
                    </h2>

                    <?php if ($question['type'] === 'multiple_choice'): ?>
                        <?php foreach ((array) $question['options'] as $optionKey => $optionValue): ?>
                            <label class="app-option">
                                <input
                                    type="radio"
                                    name="<?= htmlspecialchars($fieldName) ?>"
                                    value="<?= htmlspecialchars((string) $optionKey) ?>"
                                    <?= $previousAnswer === (string) $optionKey ? 'checked' : '' ?>>
                                <strong><?= htmlspecialchars((string) $optionKey) ?>.</strong>
                                <?= htmlspecialchars((string) $optionValue) ?>
                            </label>
                        <?php endforeach; ?>
                    <?php elseif ($question['type'] === 'fill_blank'): ?>
                        <div class="field">
                            <div class="control">
                                <input
                                    class="input"
                                    type="text"
                                    name="<?= htmlspecialchars($fieldName) ?>"
                                    value="<?= htmlspecialchars($previousAnswer) ?>"
                                    placeholder="Digite sua resposta">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="notification is-warning is-light">
                            Tipo de questão não suportado:
                            <?= htmlspecialchars((string) $question['type']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($results[$index])): ?>
                        <div class="notification <?= $results[$index]['is_correct'] ? 'is-success' : 'is-danger' ?> is-light mt-4">
                            <strong><?= $results[$index]['is_correct'] ? 'Correto' : 'Errado' ?></strong>

                            <p class="mt-2">
                                <strong>Sua resposta:</strong>
                                <?= htmlspecialchars((string) ($results[$index]['user_answer'] ?: 'Não respondida')) ?>
                            </p>

                            <p>
                                <strong>Resposta correta:</strong>
                                <?= htmlspecialchars((string) $results[$index]['correct_answer']) ?>
                            </p>

                            <?php if ((string) $question['explanation'] !== ''): ?>
                                <p class="mt-2">
                                    <strong>Explicação:</strong>
                                    <?= htmlspecialchars((string) $question['explanation']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="buttons">
                <button class="button is-dark" type="submit">Corrigir prova</button>
                <a class="button is-light" href="/exam.php?id=<?= urlencode($examId) ?>">Limpar respostas</a>
            </div>
        </form>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/templates/layout/footer.php'; ?>