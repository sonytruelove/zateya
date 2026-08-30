<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Src\Domain\Shared\Clock;

final class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $moment)
    {
    }

    public static function at(string $iso): self
    {
        return new self(new DateTimeImmutable($iso));
    }

    public function now(): DateTimeImmutable
    {
        return $this->moment;
    }

    public function set(string $iso): void
    {
        $this->moment = new DateTimeImmutable($iso);
    }
}
