<?php

declare(strict_types=1);

namespace Src\Domain\Shared;

use DateTimeImmutable;

/**
 * Источник текущего времени. Позволяет доменному слою не обращаться к global now()
 * и делает поведение, зависящее от времени, воспроизводимым в тестах.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
