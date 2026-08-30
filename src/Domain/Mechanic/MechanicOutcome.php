<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic;

/**
 * Результат розыгрыша одной попытки: выигрыш/проигрыш, набранные очки и пояснение.
 */
final readonly class MechanicOutcome
{
    private function __construct(
        public bool $won,
        public int $score,
        public string $detail,
    ) {
    }

    public static function win(int $score, string $detail): self
    {
        return new self(true, max(0, $score), $detail);
    }

    public static function lose(int $score, string $detail): self
    {
        return new self(false, max(0, $score), $detail);
    }
}
