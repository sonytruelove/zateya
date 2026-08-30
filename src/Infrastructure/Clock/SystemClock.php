<?php

declare(strict_types=1);

namespace Src\Infrastructure\Clock;

use DateTimeImmutable;
use Src\Domain\Shared\Clock;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
