<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

/**
 * Человекочитаемый адрес кампании в ссылке: строчные латинские буквы, цифры и дефис.
 */
final readonly class Slug
{
    private const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const MIN = 3;
    private const MAX = 64;

    private function __construct(public string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        $length = strlen($normalized);

        if ($length < self::MIN || $length > self::MAX) {
            throw new InvalidSlug("Адрес «{$value}»: длина {$length}, допустимо от " . self::MIN . ' до ' . self::MAX . '.');
        }

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw new InvalidSlug("Адрес «{$value}» содержит недопустимые символы; разрешены a-z, 0-9 и дефис.");
        }

        return new self($normalized);
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
