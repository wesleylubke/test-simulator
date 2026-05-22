<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\AuthService;
use App\GoogleAccessTokenService;
use App\FirestoreRestRepository;
use App\ValidationException;

AuthService::requireLogin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode((string) $raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE);
    exit;
}

$examId = trim((string) ($payload['examId'] ?? ''));
$examTitle = (string) ($payload['examTitle'] ?? '');
$answers = $payload['answers'] ?? null;

if ($examId === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'examId is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($examTitle === '') {
    $examTitle = 'Prova';
}

if (!is_array($answers)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'answers must be an array'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');
    if (!is_string($credentialsPath) || $credentialsPath === '') {
        throw new ValidationException('Variável GOOGLE_APPLICATION_CREDENTIALS não configurada.');
    }

    $tokenService = new GoogleAccessTokenService($credentialsPath);
    $repository = new FirestoreRestRepository($tokenService);

    $totalQuestions = count($answers);

    $totalCorrect = 0;
    $totalWrong = 0;

    $answersToSave = [];

    foreach ($answers as $index => $answer) {
        if (!is_array($answer)) {
            continue;
        }

        $questionId = (string) ($answer['questionId'] ?? '');
        $userAnswer = (string) ($answer['userAnswer'] ?? '');
        $correctAnswer = (string) ($answer['correctAnswer'] ?? '');
        $isCorrect = (bool) ($answer['isCorrect'] ?? false);

        if ($questionId === '') {
            $questionId = (string) $index;
        }

        $isCorrect ? $totalCorrect++ : $totalWrong++;

        $answersToSave[$questionId] = [
            'question_id' => $questionId,
            'user_answer' => $userAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
        ];
    }

    $attemptId = $repository->saveAttempt(
        $examId,
        $examTitle,
        $totalQuestions,
        $totalCorrect,
        $totalWrong,
        $answersToSave
    );

    echo json_encode([
        'ok' => true,
        'attemptId' => $attemptId,
        'totalCorrect' => $totalCorrect,
        'totalWrong' => $totalWrong,
        'totalQuestions' => $totalQuestions,
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

