<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\CampaignTransitionNotAllowed;
use Src\Domain\Campaign\InvalidCampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;

final class BoundaryTest extends TestCase
{
    #[Test]
    public function slug_of_exactly_the_minimum_length_is_accepted_but_one_shorter_is_not(): void
    {
        self::assertSame('abc', (string) Slug::fromString('abc'));

        $this->expectException(\Src\Domain\Campaign\InvalidSlug::class);
        Slug::fromString('ab');
    }

    #[Test]
    public function slug_of_exactly_the_maximum_length_is_accepted_but_one_longer_is_not(): void
    {
        $max = str_repeat('a', 64);
        self::assertSame($max, (string) Slug::fromString($max));

        $this->expectException(\Src\Domain\Campaign\InvalidSlug::class);
        Slug::fromString(str_repeat('a', 65));
    }

    #[Test]
    public function a_title_of_exactly_three_characters_is_accepted_and_two_is_rejected(): void
    {
        $this->makeDraftWithTitle('Три');
        $this->addToAssertionCount(1);

        $this->expectException(CampaignTransitionNotAllowed::class);
        $this->makeDraftWithTitle('Ой');
    }

    #[Test]
    public function a_title_of_exactly_one_hundred_forty_characters_is_accepted_and_longer_is_rejected(): void
    {
        $this->makeDraftWithTitle(str_repeat('я', 140));
        $this->addToAssertionCount(1);

        $this->expectException(CampaignTransitionNotAllowed::class);
        $this->makeDraftWithTitle(str_repeat('я', 141));
    }

    #[Test]
    public function attempts_of_one_and_one_hundred_are_accepted_but_zero_and_one_hundred_one_are_not(): void
    {
        $this->makeDraftWithAttempts(1);
        $this->makeDraftWithAttempts(100);
        $this->addToAssertionCount(2);

        $this->expectException(CampaignTransitionNotAllowed::class);
        $this->makeDraftWithAttempts(101);
    }

    #[Test]
    public function attempts_of_zero_are_rejected(): void
    {
        $this->expectException(CampaignTransitionNotAllowed::class);
        $this->makeDraftWithAttempts(0);
    }

    #[Test]
    public function a_period_whose_start_equals_its_end_is_rejected(): void
    {
        $moment = new DateTimeImmutable('2026-01-01T00:00:00Z');

        $this->expectException(\Src\Domain\Campaign\InvalidCampaignPeriod::class);
        CampaignPeriod::between($moment, $moment);
    }

    #[Test]
    public function is_over_is_true_exactly_at_the_end_instant(): void
    {
        $period = CampaignPeriod::between(
            new DateTimeImmutable('2026-01-01T00:00:00Z'),
            new DateTimeImmutable('2026-02-01T00:00:00Z'),
        );

        self::assertTrue($period->isOver(new DateTimeImmutable('2026-02-01T00:00:00Z')));
        self::assertFalse($period->isOver(new DateTimeImmutable('2026-01-31T23:59:59Z')));
    }

    #[Test]
    public function starts_in_future_is_false_exactly_at_the_start_instant(): void
    {
        $period = CampaignPeriod::between(
            new DateTimeImmutable('2026-01-10T00:00:00Z'),
            new DateTimeImmutable('2026-01-20T00:00:00Z'),
        );

        self::assertFalse($period->startsInFuture(new DateTimeImmutable('2026-01-10T00:00:00Z')));
        self::assertTrue($period->startsInFuture(new DateTimeImmutable('2026-01-09T23:59:59Z')));
    }

    #[Test]
    public function an_emoji_of_eight_characters_is_accepted_and_nine_is_rejected(): void
    {
        self::assertSame('aaaaaaaa', CampaignTheme::of('#0b57d0', 'aaaaaaaa')->emoji);

        $this->expectException(InvalidCampaignTheme::class);
        CampaignTheme::of('#0b57d0', 'aaaaaaaaa');
    }

    #[Test]
    public function only_quiz_and_wheel_are_marked_implemented(): void
    {
        self::assertTrue(MechanicType::Quiz->isImplemented());
        self::assertTrue(MechanicType::Wheel->isImplemented());
        self::assertFalse(MechanicType::Collection->isImplemented());
        self::assertFalse(MechanicType::Promo->isImplemented());
    }

    private function makeDraftWithTitle(string $title): Campaign
    {
        return Campaign::createDraft(
            CampaignId::generate(),
            Slug::fromString('boundary-title'),
            $title,
            MechanicType::Wheel,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-02-01')),
            CampaignTheme::default(),
            3,
        );
    }

    private function makeDraftWithAttempts(int $attempts): Campaign
    {
        return Campaign::createDraft(
            CampaignId::generate(),
            Slug::fromString('boundary-attempts'),
            'Граничная кампания',
            MechanicType::Wheel,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-02-01')),
            CampaignTheme::default(),
            $attempts,
        );
    }
}
