<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic\Quiz;

use Src\Domain\Mechanic\InvalidMechanicInput;

final readonly class QuizQuestion
{
    public function __construct(
        public string $id,
        public string $correctOptionId,
        public int $points,
    ) {
        if ($points < 1) {
            throw new InvalidMechanicInput("Вопрос «{$id}»: очки {$points} должны быть положительными.");
        }
    }

    public function scoreFor(string $optionId): int
    {
        return $optionId === $this->correctOptionId ? $this->points : 0;
    }
}
