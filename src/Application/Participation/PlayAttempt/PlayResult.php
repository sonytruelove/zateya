<?php

declare(strict_types=1);

namespace Src\Application\Participation\PlayAttempt;

final readonly class PlayResult
{
    public function __construct(
        public bool $won,
        public int $score,
        public string $detail,
        public int $attemptsLeft,
        public ?string $prizeTitle,
        public ?string $promoCode,
    ) {
    }
}
