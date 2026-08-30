<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Campaign;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Domain\Campaign\InvalidSlug;
use Src\Domain\Campaign\Slug;

final class SlugTest extends TestCase
{
    #[Test]
    public function it_lowercases_and_trims(): void
    {
        self::assertSame('new-year-2026', (string) Slug::fromString('  New-Year-2026 '));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'too short' => ['ab'];
        yield 'space inside' => ['new year'];
        yield 'leading hyphen' => ['-promo'];
        yield 'double hyphen' => ['promo--code'];
        yield 'symbol' => ['promo!'];
    }

    #[Test]
    #[DataProvider('invalidValues')]
    public function it_rejects_invalid_values(string $value): void
    {
        $this->expectException(InvalidSlug::class);

        Slug::fromString($value);
    }
}
