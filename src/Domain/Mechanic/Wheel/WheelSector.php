<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic\Wheel;

use Src\Domain\Mechanic\InvalidMechanicInput;

final readonly class WheelSector
{
    public function __construct(
        public string $label,
        public int $weight,
        public bool $winning,
        public int $points,
    ) {
        if ($weight < 1) {
            throw new InvalidMechanicInput("Сектор «{$label}»: вес {$weight} должен быть положительным.");
        }

        if ($points < 0) {
            throw new InvalidMechanicInput("Сектор «{$label}»: очки {$points} не могут быть отрицательными.");
        }
    }
}
