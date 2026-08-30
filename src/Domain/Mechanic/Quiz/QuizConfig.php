<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic\Quiz;

use Src\Domain\Mechanic\InvalidMechanicInput;

final readonly class QuizConfig
{
    /**
     * @param list<QuizQuestion> $questions
     */
    public function __construct(
        public array $questions,
        public int $winThreshold,
    ) {
        if ($questions === []) {
            throw new InvalidMechanicInput('Викторина должна содержать хотя бы один вопрос.');
        }

        if ($winThreshold < 1) {
            throw new InvalidMechanicInput("Порог победы {$winThreshold} должен быть положительным.");
        }
    }

    public function questionById(string $id): ?QuizQuestion
    {
        foreach ($this->questions as $question) {
            if ($question->id === $id) {
                return $question;
            }
        }

        return null;
    }
}
