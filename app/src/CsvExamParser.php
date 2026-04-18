<?php
declare(strict_types=1);

namespace App;

final class CsvExamParser
{
    /** @var string[] */
    private const REQUIRED_HEADERS = [
        'exam_title',
        'question_id',
        'question_type',
        'statement',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'explanation',
    ];

    /** @var string[] */
    private const ALLOWED_TYPES = [
        'multiple_choice',
        'fill_blank',
    ];

    public function parse(string $filePath): ParsedExam
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new ValidationException('Arquivo CSV não encontrado ou sem permissão de leitura.');
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new ValidationException('Não foi possível abrir o arquivo CSV.');
        }

        try {
            $headerRow = fgetcsv($handle);
            if ($headerRow === false) {
                throw new ValidationException('O arquivo CSV está vazio.');
            }

            $headers = array_map([$this, 'normalizeHeader'], $headerRow);
            $this->validateHeaders($headers);

            $questions = [];
            $examTitle = null;
            $seenQuestionIds = [];
            $orderIndex = 1;
            $lineNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $data = $this->combineRow($headers, $row, $lineNumber);

                $currentExamTitle = $this->requireNonEmpty($data, 'exam_title', $lineNumber);
                if ($examTitle === null) {
                    $examTitle = $currentExamTitle;
                } elseif ($examTitle !== $currentExamTitle) {
                    throw new ValidationException(
                        "Linha {$lineNumber}: todas as linhas devem ter o mesmo exam_title."
                    );
                }

                $questionId = $this->requireNonEmpty($data, 'question_id', $lineNumber);
                if (isset($seenQuestionIds[$questionId])) {
                    throw new ValidationException(
                        "Linha {$lineNumber}: question_id duplicado '{$questionId}'."
                    );
                }
                $seenQuestionIds[$questionId] = true;

                $questionType = $this->requireNonEmpty($data, 'question_type', $lineNumber);
                if (!in_array($questionType, self::ALLOWED_TYPES, true)) {
                    throw new ValidationException(
                        "Linha {$lineNumber}: question_type inválido '{$questionType}'."
                    );
                }

                $statement = $this->requireNonEmpty($data, 'statement', $lineNumber);
                $correctAnswer = $this->requireNonEmpty($data, 'correct_answer', $lineNumber);
                $explanation = trim((string) ($data['explanation'] ?? ''));

                $question = [
                    'question_id' => $questionId,
                    'question_type' => $questionType,
                    'statement' => $statement,
                    'options' => [],
                    'correct_answer' => '',
                    'explanation' => $explanation,
                    'order_index' => $orderIndex++,
                ];

                if ($questionType === 'multiple_choice') {
                    $options = [
                        'A' => $this->requireNonEmpty($data, 'option_a', $lineNumber),
                        'B' => $this->requireNonEmpty($data, 'option_b', $lineNumber),
                        'C' => $this->requireNonEmpty($data, 'option_c', $lineNumber),
                        'D' => $this->requireNonEmpty($data, 'option_d', $lineNumber),
                    ];

                    if (!in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
                        throw new ValidationException(
                            "Linha {$lineNumber}: correct_answer deve ser A, B, C ou D para multiple_choice."
                        );
                    }

                    $question['options'] = $options;
                    $question['correct_answer'] = $correctAnswer;
                }

                if ($questionType === 'fill_blank') {
                    $this->ensureBlankOptionFields($data, $lineNumber);
                    $question['options'] = [];
                    $question['correct_answer'] = $correctAnswer;
                }

                $questions[] = $question;
            }

            if ($examTitle === null || $questions === []) {
                throw new ValidationException('Nenhuma questão válida foi encontrada no CSV.');
            }

            return new ParsedExam($examTitle, $questions);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param string[] $headers
     */
    private function validateHeaders(array $headers): void
    {
        foreach (self::REQUIRED_HEADERS as $requiredHeader) {
            if (!in_array($requiredHeader, $headers, true)) {
                throw new ValidationException("Cabeçalho obrigatório ausente: {$requiredHeader}");
            }
        }
    }

    /**
     * @param string[] $headers
     * @param array<int, string|null> $row
     * @return array<string, string>
     */
    private function combineRow(array $headers, array $row, int $lineNumber): array
    {
        if (count($row) !== count($headers)) {
            throw new ValidationException(
                "Linha {$lineNumber}: quantidade de colunas inválida. Esperado " .
                count($headers) . ', recebido ' . count($row) . '.'
            );
        }

        $combined = array_combine($headers, $row);
        if ($combined === false) {
            throw new ValidationException("Linha {$lineNumber}: falha ao processar colunas do CSV.");
        }

        return array_map(
            static fn ($value): string => trim((string) $value),
            $combined
        );
    }

    /**
     * @param array<string, string> $data
     */
    private function requireNonEmpty(array $data, string $field, int $lineNumber): string
    {
        $value = trim((string) ($data[$field] ?? ''));
        if ($value === '') {
            throw new ValidationException("Linha {$lineNumber}: campo obrigatório vazio '{$field}'.");
        }

        return $value;
    }

    /**
     * @param array<string, string> $data
     */
    private function ensureBlankOptionFields(array $data, int $lineNumber): void
    {
        foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $field) {
            if (trim((string) ($data[$field] ?? '')) !== '') {
                throw new ValidationException(
                    "Linha {$lineNumber}: {$field} deve estar vazio para fill_blank."
                );
            }
        }
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim($header));
    }

    /**
     * @param array<int, string|null> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}