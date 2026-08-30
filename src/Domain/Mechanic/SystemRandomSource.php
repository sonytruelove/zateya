<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic;

final class SystemRandomSource implements RandomSource
{
    public function int(int $min, int $max): int
    {
        if ($min > $max) {
            throw new InvalidMechanicInput("Нижняя граница {$min} больше верхней {$max}.");
        }

        return random_int($min, $max);
    }
}
