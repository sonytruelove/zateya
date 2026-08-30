<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Campaign;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\InvalidCampaignPeriod;

final class CampaignPeriodTest extends TestCase
{
    #[Test]
    public function start_must_be_before_end(): void
    {
        $this->expectException(InvalidCampaignPeriod::class);

        CampaignPeriod::between(new DateTimeImmutable('2026-02-01'), new DateTimeImmutable('2026-01-01'));
    }

    #[Test]
    public function contains_is_inclusive_of_start_and_exclusive_of_end(): void
    {
        $period = CampaignPeriod::between(new DateTimeImmutable('2026-01-01T00:00:00Z'), new DateTimeImmutable('2026-02-01T00:00:00Z'));

        self::assertTrue($period->contains(new DateTimeImmutable('2026-01-01T00:00:00Z')));
        self::assertTrue($period->contains(new DateTimeImmutable('2026-01-15T12:00:00Z')));
        self::assertFalse($period->contains(new DateTimeImmutable('2026-02-01T00:00:00Z')));
    }

    #[Test]
    public function it_reports_future_start_and_finished_period(): void
    {
        $period = CampaignPeriod::between(new DateTimeImmutable('2026-01-10'), new DateTimeImmutable('2026-01-20'));

        self::assertTrue($period->startsInFuture(new DateTimeImmutable('2026-01-05')));
        self::assertFalse($period->startsInFuture(new DateTimeImmutable('2026-01-15')));
        self::assertTrue($period->isOver(new DateTimeImmutable('2026-01-21')));
    }
}
