<?php

declare(strict_types=1);

namespace Tests\Integration;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignStatus;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Infrastructure\Persistence\Eloquent\EloquentCampaignRepository;
use Tests\TestCase;

final class EloquentCampaignRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_round_trips_a_campaign_and_preserves_its_status(): void
    {
        $repository = new EloquentCampaignRepository();
        $id = CampaignId::generate();
        $campaign = Campaign::createDraft(
            $id,
            Slug::fromString('round-trip'),
            'Круговая кампания',
            MechanicType::Quiz,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-06-01')),
            CampaignTheme::of('#0b57d0', 'Q'),
            7,
        );
        $campaign->publish(new DateTimeImmutable('2026-02-01'));

        $repository->save($campaign);
        $loaded = $repository->bySlug(Slug::fromString('round-trip'));

        self::assertTrue($loaded->id->equals($id));
        self::assertSame(CampaignStatus::Active, $loaded->status());
        self::assertSame(7, $loaded->attemptsPerParticipant());
        self::assertSame('Круговая кампания', $loaded->title());
    }
}
