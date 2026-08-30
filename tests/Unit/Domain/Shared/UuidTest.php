<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Domain\Shared\InvalidUuid;
use Src\Domain\Shared\Uuid;

final class UuidTest extends TestCase
{
    #[Test]
    public function generated_value_matches_uuid_shape(): void
    {
        $uuid = Uuid::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string) $uuid,
        );
    }

    #[Test]
    public function two_generated_values_are_distinct(): void
    {
        self::assertNotSame((string) Uuid::generate(), (string) Uuid::generate());
    }

    #[Test]
    public function it_normalises_case_and_compares_by_value(): void
    {
        $lower = Uuid::fromString('3f2504e0-4f89-41d3-9a0c-0305e82c3301');
        $upper = Uuid::fromString('3F2504E0-4F89-41D3-9A0C-0305E82C3301');

        self::assertTrue($lower->equals($upper));
    }

    #[Test]
    public function it_rejects_a_malformed_string_with_the_offending_value(): void
    {
        $this->expectException(InvalidUuid::class);
        $this->expectExceptionMessage('not-a-uuid');

        Uuid::fromString('not-a-uuid');
    }
}
