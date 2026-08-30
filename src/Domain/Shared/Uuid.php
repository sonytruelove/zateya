<?php

declare(strict_types=1);

namespace Src\Domain\Shared;

/**
 * Объект-значение «универсальный идентификатор». Проверяет форму строки при создании,
 * поэтому недопустимый идентификатор невозможно передать дальше по коду.
 */
final readonly class Uuid
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw new InvalidUuid("Строка «{$value}» не является идентификатором в форме UUID.");
        }

        return new self($normalized);
    }

    public static function generate(): self
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
