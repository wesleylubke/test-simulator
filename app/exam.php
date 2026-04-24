<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\GoogleAccessTokenService;

$examId = trim((string) ($_GET['id'] ?? ''));
$errorMessage = null;
$exam = null;
$questions = [];
$results = [];
$totalCorrect = 0;
$totalWrong = 0;

function firestoreValueToPhp(array $value): mixed
{
    if (isset($value['stringValue'])) {
        return $value['stringValue'];
    }

    if (isset($value['integerValue'])) {
        return (int) $value['integerValue'];
    }

    if (isset($value['doubleValue'])) {
        return (float) $value['doubleValue'];
    }

    if (isset($value['booleanValue'])) {
        return (bool) $value['booleanValue'];
    }

    if (isset($value['timestampValue'])) {
        return $value['timestampValue'];
    }

    if (isset($value['mapValue'])) {
        $fields = $value['mapValue']['fields'] ?? [];
        $result = [];

        foreach ($fields as $key => $fieldValue) {
            $result[$key] = firestoreValueToPhp($fieldValue);
        }

        return $result;
    }

    if (isset($value['arrayValue'])) {
        $values = $value['arrayValue']['values'] ?? [];
        return array_map('firestoreValueToPhp', $values);
    }

    return null;
}

function firestoreRequest(string $url): array
{
    $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');

    if (!is_string($credentialsPath) || $credentialsPath === '') {
        throw new RuntimeException('Variável GOOGLE_APPLICATION_CREDENTIALS não configurada.');
    }

    $tokenService = new GoogleAccessTokenService($credentialsPath);
    $token = $tokenService->getAccessToken();

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

    $examUrl = $baseUrl . '/exams/' . rawurlencode($examId);
    $examResponse = firestoreRequest($examUrl);

    $examFields = $examResponse['fields'] ?? [];

    $exam = [
        'id' => $examId,
        'title' => firestoreValueToPhp($examFields['title'] ?? []) ?? 'Prova sem título',
        'total_questions' => firestoreValueToPhp($examFields['total_questions'] ?? []) ?? 0,
        'created_at' => firestoreValueToPhp($examFields['created_at'] ?? []) ?? '',
        'status' => firestoreValueToPhp($examFields['status'] ?? []) ?? '',
    ];

    $questionsUrl = $baseUrl . '/exams/' . rawurlencode($examId) . '/questions?pageSize=200';
    $questionsResponse = firestoreRequest($questionsUrl);

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
        static fn (array $a, array $b): int => ((int) $a['order_index']) <=> ((int) $b['order_index'])
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($questions as $index => $question) {
            $fieldName = 'answer_' . $index;
            $userAnswer = trim((string) ($_POST[$fieldName] ?? ''));
            $correctAnswer = trim((string) $question['correct_answer']);

            $isCorrect = mb_strtolower($userAnswer) === mb_strtolower($correctAnswer);

            if ($isCorrect) {
                $totalCorrect++;
            } else {
                $totalWrong++;
            }

            $results[$index] = [
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
            ];
        }
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($exam['title'] ?? 'Prova')) ?> - Test Simulator</title>
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
            --warning-bg: #fffbeb;
            --warning-text: #92400e;
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

        h1, h2, h3 {
            margin-top: 0;
        }

        .muted {
            color: var(--muted);
        }

        .question {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
            background: #fff;
        }

        .question-header {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .option {
            display: block;
            margin: 8px 0;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
        }

        .option:hover {
            background: #f9fafb;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 15px;
        }

        button, .button {
            display: inline-block;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }

        button:hover, .button:hover {
            opacity: 0.92;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert.error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .alert.warning {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .result-ok {
            color: var(--success-text);
            font-weight: bold;
        }

        .result-bad {
            color: var(--error-text);
            font-weight: bold;
        }

        .answer-box {
            margin-top: 12px;
            padding: 12px;
            border-radius: 10px;
            background: #f9fafb;
            border: 1px solid var(--border);
        }

        .top-actions {
            margin-bottom: 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .summary-box {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            background: #fafafa;
        }

        .explanation {
            margin-top: 8px;
            color: var(--muted);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="top-actions">
        <a class="button" href="/index.php">← Voltar para provas</a>
    </div>

    <?php if ($errorMessage !== null): ?>
        <div class="card">
            <h1>Erro ao carregar prova</h1>
            <div class="alert error"><?= htmlspecialchars($errorMessage) ?></div>
        </div>
    <?php elseif ($exam !== null): ?>
        <div class="card">
            <h1><?= htmlspecialchars((string) $exam['title']) ?></h1>
            <p class="muted">
                ID: <?= htmlspecialchars((string) $exam['id']) ?>
                · Questões: <?= count($questions) ?>
                <?php if ((string) $exam['status'] !== ''): ?>
                    · Status: <?= htmlspecialchars((string) $exam['status']) ?>
                <?php endif; ?>
            </p>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="summary-grid">
                    <div class="summary-box">
                        <strong>Acertos</strong>
                        <div><?= (int) $totalCorrect ?></div>
                    </div>
                    <div class="summary-box">
                        <strong>Erros</strong>
                        <div><?= (int) $totalWrong ?></div>
                    </div>
                    <div class="summary-box">
                        <strong>Total</strong>
                        <div><?= count($questions) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($questions)): ?>
            <div class="card">
                <div class="alert warning">Nenhuma questão encontrada para esta prova.</div>
            </div>
        <?php else: ?>
            <form method="post">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question">
                        <div class="question-header">
                            Questão <?= $index + 1 ?>
                            · <?= htmlspecialchars((string) $question['question_id']) ?>
                            · <?= htmlspecialchars((string) $question['type']) ?>
                        </div>

                        <h3><?= htmlspecialchars((string) $question['statement']) ?></h3>

                        <?php
                        $fieldName = 'answer_' . $index;
                        $previousAnswer = (string) ($_POST[$fieldName] ?? '');
                        ?>

                        <?php if ($question['type'] === 'multiple_choice'): ?>
                            <?php foreach ((array) $question['options'] as $optionKey => $optionValue): ?>
                                <label class="option">
                                    <input
                                        type="radio"
                                        name="<?= htmlspecialchars($fieldName) ?>"
                                        value="<?= htmlspecialchars((string) $optionKey) ?>"
                                        <?= $previousAnswer === (string) $optionKey ? 'checked' : '' ?>
                                    >
                                    <strong><?= htmlspecialchars((string) $optionKey) ?>.</strong>
                                    <?= htmlspecialchars((string) $optionValue) ?>
                                </label>
                            <?php endforeach; ?>
                        <?php elseif ($question['type'] === 'fill_blank'): ?>
                            <input
                                type="text"
                                name="<?= htmlspecialchars($fieldName) ?>"
                                value="<?= htmlspecialchars($previousAnswer) ?>"
                                placeholder="Digite sua resposta"
                            >
                        <?php else: ?>
                            <div class="alert warning">
                                Tipo de questão não suportado:
                                <?= htmlspecialchars((string) $question['type']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($results[$index])): ?>
                            <div class="answer-box">
                                <?php if ($results[$index]['is_correct']): ?>
                                    <div class="result-ok">Correto</div>
                                <?php else: ?>
                                    <div class="result-bad">Errado</div>
                                <?php endif; ?>

                                <p>
                                    <strong>Sua resposta:</strong>
                                    <?= htmlspecialchars((string) ($results[$index]['user_answer'] ?: 'Não respondida')) ?>
                                </p>

                                <p>
                                    <strong>Resposta correta:</strong>
                                    <?= htmlspecialchars((string) $results[$index]['correct_answer']) ?>
                                </p>

                                <?php if ((string) $question['explanation'] !== ''): ?>
                                    <p class="explanation">
                                        <strong>Explicação:</strong>
                                        <?= htmlspecialchars((string) $question['explanation']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit">Corrigir prova</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>