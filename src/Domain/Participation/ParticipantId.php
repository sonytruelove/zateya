<?php

declare(strict_types=1);

namespace Src\Domain\Participation;

use Src\Domain\Shared\Uuid;

final readonly class ParticipantId
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

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
