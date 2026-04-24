<?php

declare(strict_types=1);

namespace App;

final class FirestoreRestRepository
{
    private string $projectId;
    private string $baseUrl;
    private GoogleAccessTokenService $tokenService;

    public function __construct(GoogleAccessTokenService $tokenService)
    {
        $this->projectId = getenv('GOOGLE_CLOUD_PROJECT') ?: '';
        if ($this->projectId === '') {
            throw new ValidationException('Variável GOOGLE_CLOUD_PROJECT não configurada.');
        }

        $this->baseUrl = sprintf(
            'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents',
            $this->projectId
        );

        $this->tokenService = $tokenService;
    }

    public function saveExam(ParsedExam $exam, string $csvPath = ''): string
    {
        $payload = [
            'fields' => [
                'title' => ['stringValue' => $exam->title],
                'created_at' => ['timestampValue' => gmdate('Y-m-d\\TH:i:s\\Z')],
                'total_questions' => ['integerValue' => (string) $exam->totalQuestions()],
                'csv_path' => ['stringValue' => $csvPath],
                'status' => ['stringValue' => 'processed'],
                'is_deleted' => ['booleanValue' => false],
            ]
        ];

        $response = $this->request('POST', $this->baseUrl . '/exams', $payload);

        if (!isset($response['name'])) {
            throw new ValidationException('Resposta inválida ao salvar prova no Firestore.');
        }

        $parts = explode('/', $response['name']);
        return end($parts);
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    public function saveQuestions(string $examId, array $questions): void
    {
        foreach ($questions as $question) {
            $questionId = (string) $question['question_id'];

            $payload = [
                'fields' => [
                    'question_id' => ['stringValue' => $questionId],
                    'statement' => ['stringValue' => (string) $question['statement']],
                    'type' => ['stringValue' => (string) $question['question_type']],
                    'options' => $this->toFirestoreValue((array) $question['options']),
                    'correct_answer' => ['stringValue' => (string) $question['correct_answer']],
                    'explanation' => ['stringValue' => (string) $question['explanation']],
                    'order_index' => ['integerValue' => (string) $question['order_index']],
                ]
            ];

            $url = sprintf(
                '%s/exams/%s/questions?documentId=%s',
                $this->baseUrl,
                rawurlencode($examId),
                rawurlencode($questionId)
            );

            $this->request('POST', $url, $payload);
        }
    }

    private function request(string $method, string $url, array $payload): array
    {
        $token = $this->tokenService->getAccessToken();

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new ValidationException('Erro CURL Firestore: ' . curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $decoded = json_decode($response, true);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ValidationException(
                'Erro Firestore REST (' . $statusCode . '): ' . json_encode($decoded, JSON_UNESCAPED_UNICODE)
            );
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function toFirestoreValue(mixed $value): array
    {
        if (is_string($value)) {
            return ['stringValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_array($value)) {
            if ($value === []) {
                return ['mapValue' => ['fields' => new \stdClass()]];
            }

            if ($this->isAssociativeArray($value)) {
                $fields = [];

                foreach ($value as $key => $item) {
                    $fields[(string) $key] = $this->toFirestoreValue($item);
                }

                return ['mapValue' => ['fields' => $fields]];
            }

            $values = [];

            foreach ($value as $item) {
                $values[] = $this->toFirestoreValue($item);
            }

            return ['arrayValue' => ['values' => $values]];
        }

        if ($value === null) {
            return ['nullValue' => null];
        }

        return ['stringValue' => (string) $value];
    }

    /**
     * @param array<mixed> $array
     */
    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
