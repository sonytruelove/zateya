<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic\Wheel;

use Src\Domain\Mechanic\InvalidMechanicInput;

final readonly class WheelConfig
{
    /**
     * @param list<WheelSector> $sectors
     */
    public function __construct(public array $sectors)
    {
        if ($sectors === []) {
            throw new InvalidMechanicInput('Колесо фортуны должно содержать хотя бы один сектор.');
        }
    }

    public function totalWeight(): int
    {
        return array_sum(array_map(static fn (WheelSector $s): int => $s->weight, $this->sectors));
    }
}
