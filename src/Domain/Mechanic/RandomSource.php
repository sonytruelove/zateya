<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic;

/**
 * Источник случайности для механик. Вынесен в интерфейс, чтобы розыгрыш
 * был воспроизводимым в тестах (подставляется предопределённая последовательность).
 */
interface RandomSource
{
    /**
     * Целое в диапазоне [$min, $max] включительно.
     */
    public function int(int $min, int $max): int;
}
