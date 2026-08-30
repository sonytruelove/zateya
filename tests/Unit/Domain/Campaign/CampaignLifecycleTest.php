<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Campaign;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignStatus;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\CampaignTransitionNotAllowed;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Domain\Event\CampaignPublished;

final class CampaignLifecycleTest extends TestCase
{
    #[Test]
    public function publishing_a_running_period_activates_and_records_an_event(): void
    {
        $campaign = $this->draft('2026-01-01', '2026-03-01');

        $campaign->publish(new DateTimeImmutable('2026-01-15'));

        self::assertSame(CampaignStatus::Active, $campaign->status());
        $events = $campaign->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CampaignPublished::class, $events[0]);
    }

    #[Test]
    public function publishing_a_future_period_schedules_it(): void
    {
        $campaign = $this->draft('2026-05-01', '2026-06-01');

        $campaign->publish(new DateTimeImmutable('2026-04-01'));

        self::assertSame(CampaignStatus::Scheduled, $campaign->status());
    }

    #[Test]
    public function events_are_released_only_once(): void
    {
        $campaign = $this->draft('2026-01-01', '2026-03-01');
        $campaign->publish(new DateTimeImmutable('2026-01-15'));

        self::assertCount(1, $campaign->releaseEvents());
        self::assertCount(0, $campaign->releaseEvents());
    }

    #[Test]
    public function pause_and_resume_toggle_between_active_and_paused(): void
    {
        $campaign = $this->draft('2026-01-01', '2026-03-01');
        $campaign->publish(new DateTimeImmutable('2026-01-15'));

        $campaign->pause();
        self::assertSame(CampaignStatus::Paused, $campaign->status());

        $campaign->resume();
        self::assertSame(CampaignStatus::Active, $campaign->status());
    }

    #[Test]
    public function a_draft_cannot_be_paused(): void
    {
        $this->expectException(CampaignTransitionNotAllowed::class);

        $this->draft('2026-01-01', '2026-03-01')->pause();
    }

    #[Test]
    public function archiving_requires_a_finished_campaign(): void
    {
        $campaign = $this->draft('2026-01-01', '2026-03-01');
        $campaign->publish(new DateTimeImmutable('2026-01-15'));

        $this->expectException(CampaignTransitionNotAllowed::class);
        $campaign->archive();
    }

    #[Test]
    public function full_path_draft_to_archived_is_allowed(): void
    {
        $campaign = $this->draft('2026-01-01', '2026-03-01');
        $campaign->publish(new DateTimeImmutable('2026-01-15'));
        $campaign->finish();
        $campaign->archive();

        self::assertSame(CampaignStatus::Archived, $campaign->status());
    }

    #[Test]
    public function it_accepts_attempts_only_when_active_and_within_period(): void
    {
        $campaign = $this->draft('2026-01-01', '2026-03-01');
        $campaign->publish(new DateTimeImmutable('2026-01-15'));

        self::assertTrue($campaign->isAcceptingAttempts(new DateTimeImmutable('2026-02-01')));
        self::assertFalse($campaign->isAcceptingAttempts(new DateTimeImmutable('2026-04-01')));

        $campaign->pause();
        self::assertFalse($campaign->isAcceptingAttempts(new DateTimeImmutable('2026-02-01')));
    }

    #[Test]
    public function an_unimplemented_mechanic_cannot_be_published(): void
    {
        $campaign = Campaign::createDraft(
            CampaignId::generate(),
            Slug::fromString('collect-set'),
            'Собери набор',
            MechanicType::Collection,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-03-01')),
            CampaignTheme::default(),
            3,
        );

        $this->expectException(CampaignTransitionNotAllowed::class);
        $campaign->publish(new DateTimeImmutable('2026-01-15'));
    }

    private function draft(string $start, string $end): Campaign
    {
        return Campaign::createDraft(
            CampaignId::generate(),
            Slug::fromString('spring-quiz'),
            'Весенняя викторина',
            MechanicType::Quiz,
            CampaignPeriod::between(new DateTimeImmutable($start), new DateTimeImmutable($end)),
            CampaignTheme::default(),
            3,
        );
    }
}
