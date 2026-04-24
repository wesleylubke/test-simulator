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

    private function request(string $method, string $url, array $payload = []): array
    {
        $token = $this->tokenService->getAccessToken();

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);

        if (!in_array($method, ['GET', 'DELETE'], true)) {
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode($payload, JSON_UNESCAPED_UNICODE)
            );
        }

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

    public function listExams(): array
    {
        $url = $this->baseUrl . '/exams?pageSize=50';

        $response = $this->request('GET', $url);

        $exams = [];

        foreach (($response['documents'] ?? []) as $document) {
            $fields = $document['fields'] ?? [];
            $nameParts = explode('/', $document['name'] ?? '');
            $id = end($nameParts);

            $exams[] = [
                'id' => $id,
                'title' => $fields['title']['stringValue'] ?? '',
                'total_questions' => (int) ($fields['total_questions']['integerValue'] ?? 0),
                'status' => $fields['status']['stringValue'] ?? '',
                'created_at' => $fields['created_at']['timestampValue'] ?? '',
                'folder_id' => $fields['folder_id']['stringValue'] ?? '',
                'folder_name' => $fields['folder_name']['stringValue'] ?? 'Sem pasta',
            ];
        }

        return $exams;
    }

    public function deleteExam(string $examId): void
    {
        $url = $this->baseUrl . '/exams/' . rawurlencode($examId);

        $this->request('DELETE', $url);
    }

    public function saveAttempt(
        string $examId,
        string $examTitle,
        int $totalQuestions,
        int $totalCorrect,
        int $totalWrong,
        array $answers
    ): string {
        $scorePercent = $totalQuestions > 0
            ? round(($totalCorrect / $totalQuestions) * 100, 2)
            : 0;

        $payload = [
            'fields' => [
                'exam_id' => ['stringValue' => $examId],
                'exam_title' => ['stringValue' => $examTitle],
                'total_questions' => ['integerValue' => (string) $totalQuestions],
                'total_correct' => ['integerValue' => (string) $totalCorrect],
                'total_wrong' => ['integerValue' => (string) $totalWrong],
                'score_percent' => ['doubleValue' => $scorePercent],
                'created_at' => ['timestampValue' => gmdate('Y-m-d\\TH:i:s\\Z')],
                'answers' => $this->toFirestoreValue($answers),
            ],
        ];

        $response = $this->request('POST', $this->baseUrl . '/attempts', $payload);

        if (!isset($response['name'])) {
            throw new ValidationException('Resposta inválida ao salvar tentativa no Firestore.');
        }

        $parts = explode('/', $response['name']);

        return end($parts);
    }

    public function listAttempts(): array
    {
        $url = $this->baseUrl . '/attempts?pageSize=50';

        $response = $this->request('GET', $url);

        $attempts = [];

        foreach (($response['documents'] ?? []) as $document) {
            $fields = $document['fields'] ?? [];
            $nameParts = explode('/', (string) ($document['name'] ?? ''));
            $id = end($nameParts);

            $attempts[] = [
                'id' => $id,
                'exam_id' => $fields['exam_id']['stringValue'] ?? '',
                'exam_title' => $fields['exam_title']['stringValue'] ?? '',
                'total_questions' => (int) ($fields['total_questions']['integerValue'] ?? 0),
                'total_correct' => (int) ($fields['total_correct']['integerValue'] ?? 0),
                'total_wrong' => (int) ($fields['total_wrong']['integerValue'] ?? 0),
                'score_percent' => (float) ($fields['score_percent']['doubleValue'] ?? 0),
                'created_at' => $fields['created_at']['timestampValue'] ?? '',
            ];
        }

        usort(
            $attempts,
            static fn(array $a, array $b): int => strcmp((string) $b['created_at'], (string) $a['created_at'])
        );

        return $attempts;
    }

    public function getExam(string $examId): array
    {
        $url = $this->baseUrl . '/exams/' . rawurlencode($examId);

        $response = $this->request('GET', $url);

        $fields = $response['fields'] ?? [];

        return [
            'id' => $examId,
            'title' => $fields['title']['stringValue'] ?? '',
            'total_questions' => (int) ($fields['total_questions']['integerValue'] ?? 0),
            'status' => $fields['status']['stringValue'] ?? '',
            'created_at' => $fields['created_at']['timestampValue'] ?? '',
        ];
    }

    public function updateExamTitle(string $examId, string $title): void
    {
        $url = $this->baseUrl
            . '/exams/'
            . rawurlencode($examId)
            . '?updateMask.fieldPaths=title&updateMask.fieldPaths=updated_at';

        $payload = [
            'fields' => [
                'title' => ['stringValue' => $title],
                'updated_at' => ['timestampValue' => gmdate('Y-m-d\\TH:i:s\\Z')],
            ],
        ];

        $this->request('PATCH', $url, $payload);
    }

    public function listQuestions(string $examId): array
    {
        $url = $this->baseUrl . '/exams/' . rawurlencode($examId) . '/questions?pageSize=200';

        $response = $this->request('GET', $url);

        $questions = [];

        foreach (($response['documents'] ?? []) as $document) {
            $fields = $document['fields'] ?? [];
            $nameParts = explode('/', (string) ($document['name'] ?? ''));
            $id = end($nameParts);

            $questions[] = [
                'id' => $id,
                'question_id' => $fields['question_id']['stringValue'] ?? $id,
                'statement' => $fields['statement']['stringValue'] ?? '',
                'type' => $fields['type']['stringValue'] ?? '',
                'options' => [
                    'A' => $fields['options']['mapValue']['fields']['A']['stringValue'] ?? '',
                    'B' => $fields['options']['mapValue']['fields']['B']['stringValue'] ?? '',
                    'C' => $fields['options']['mapValue']['fields']['C']['stringValue'] ?? '',
                    'D' => $fields['options']['mapValue']['fields']['D']['stringValue'] ?? '',
                ],
                'correct_answer' => $fields['correct_answer']['stringValue'] ?? '',
                'explanation' => $fields['explanation']['stringValue'] ?? '',
                'order_index' => (int) ($fields['order_index']['integerValue'] ?? 0),
            ];
        }

        usort(
            $questions,
            static fn(array $a, array $b): int => ((int) $a['order_index']) <=> ((int) $b['order_index'])
        );

        return $questions;
    }

    public function updateQuestion(string $examId, string $questionId, array $question): void
    {
        $url = $this->baseUrl
            . '/exams/'
            . rawurlencode($examId)
            . '/questions/'
            . rawurlencode($questionId)
            . '?updateMask.fieldPaths=statement'
            . '&updateMask.fieldPaths=options'
            . '&updateMask.fieldPaths=correct_answer'
            . '&updateMask.fieldPaths=explanation';

        $payload = [
            'fields' => [
                'statement' => ['stringValue' => (string) $question['statement']],
                'options' => $this->toFirestoreValue((array) $question['options']),
                'correct_answer' => ['stringValue' => (string) $question['correct_answer']],
                'explanation' => ['stringValue' => (string) $question['explanation']],
            ],
        ];

        $this->request('PATCH', $url, $payload);
    }

    public function createFolder(string $name, string $color = 'blue'): string
    {
        $payload = [
            'fields' => [
                'name' => ['stringValue' => $name],
                'color' => ['stringValue' => $color],
                'created_at' => ['timestampValue' => gmdate('Y-m-d\\TH:i:s\\Z')],
                'updated_at' => ['timestampValue' => gmdate('Y-m-d\\TH:i:s\\Z')],
            ],
        ];

        $response = $this->request('POST', $this->baseUrl . '/folders', $payload);

        if (!isset($response['name'])) {
            throw new ValidationException('Resposta inválida ao criar pasta no Firestore.');
        }

        $parts = explode('/', $response['name']);

        return end($parts);
    }

    public function listFolders(): array
    {
        $url = $this->baseUrl . '/folders?pageSize=100';

        $response = $this->request('GET', $url);

        $folders = [];

        foreach (($response['documents'] ?? []) as $document) {
            $fields = $document['fields'] ?? [];
            $nameParts = explode('/', (string) ($document['name'] ?? ''));
            $id = end($nameParts);

            $folders[] = [
                'id' => $id,
                'name' => $fields['name']['stringValue'] ?? '',
                'color' => $fields['color']['stringValue'] ?? 'blue',
                'created_at' => $fields['created_at']['timestampValue'] ?? '',
                'updated_at' => $fields['updated_at']['timestampValue'] ?? '',
            ];
        }

        usort(
            $folders,
            static fn(array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name'])
        );

        return $folders;
    }

    public function updateExamFolder(string $examId, string $folderId, string $folderName): void
    {
        $url = $this->baseUrl
            . '/exams/'
            . rawurlencode($examId)
            . '?updateMask.fieldPaths=folder_id'
            . '&updateMask.fieldPaths=folder_name'
            . '&updateMask.fieldPaths=updated_at';

        $payload = [
            'fields' => [
                'folder_id' => ['stringValue' => $folderId],
                'folder_name' => ['stringValue' => $folderName],
                'updated_at' => ['timestampValue' => gmdate('Y-m-d\\TH:i:s\\Z')],
            ],
        ];

        $this->request('PATCH', $url, $payload);
    }
}
