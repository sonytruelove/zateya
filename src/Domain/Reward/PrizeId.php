<?php

declare(strict_types=1);

namespace Src\Domain\Reward;

use Src\Domain\Shared\Uuid;

final readonly class PrizeId
{
    private function __construct(public Uuid $id)
    {
    }

    public static function fromString(string $value): self
    {
        return new self(Uuid::fromString($value));
    }

    public static function generate(): self
    {
        return new self(Uuid::generate());
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
