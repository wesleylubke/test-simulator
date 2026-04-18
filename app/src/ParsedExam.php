<?php
declare(strict_types=1);

namespace App;

final class ParsedExam
{
    /**
     * @param array<int, array<string, mixed>> $questions
     */
    public function __construct(
        public readonly string $title,
        public readonly array $questions
    ) {
    }

    public function totalQuestions(): int
    {
        return count($this->questions);
    }
}