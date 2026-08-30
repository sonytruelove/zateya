<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic;

use Src\Domain\Campaign\MechanicType;

/**
 * Игровая механика кампании. Реализация детерминирована при заданном RandomSource.
 */
interface Mechanic
{
    public function type(): MechanicType;

    /**
     * @param array<string, mixed> $payload данные хода от участника
     * @throws InvalidMechanicInput если данные хода не соответствуют механике
     */
    public function validate(array $payload): void;

    /**
     * @param array<string, mixed> $payload
     */
    public function play(array $payload, RandomSource $random): MechanicOutcome;
}
