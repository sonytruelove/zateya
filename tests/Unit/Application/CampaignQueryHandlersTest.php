<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Application\Campaign\AddPrizes\AddPrizesCommand;
use Src\Application\Campaign\AddPrizes\AddPrizesHandler;
use Src\Application\Campaign\CampaignStats\CampaignStatsHandler;
use Src\Application\Campaign\CampaignStats\CampaignStatsQuery;
use Src\Application\Campaign\PublishCampaign\PublishCampaignCommand;
use Src\Application\Campaign\PublishCampaign\PublishCampaignHandler;
use Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesCommand;
use Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesHandler;
use Src\Application\Campaign\ViewCampaign\ViewCampaignHandler;
use Src\Application\Campaign\ViewCampaign\ViewCampaignQuery;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignStatus;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Infrastructure\Balance\ArrayAttemptBalance;
use Src\Infrastructure\Messaging\InMemoryEventPublisher;
use Tests\Support\FixedClock;
use Tests\Support\InMemoryAttemptRepository;
use Tests\Support\InMemoryCampaignRepository;
use Tests\Support\InMemoryParticipantRepository;
use Tests\Support\InMemoryPrizePool;
use Tests\Support\InMemoryPromoCodeBook;

final class CampaignQueryHandlersTest extends TestCase
{
    private InMemoryCampaignRepository $campaigns;
    private CampaignId $id;

    protected function setUp(): void
    {
        $this->campaigns = new InMemoryCampaignRepository();
        $this->id = CampaignId::generate();
        $campaign = Campaign::createDraft(
            $this->id,
            Slug::fromString('view-me'),
            'Кампания для чтения',
            MechanicType::Wheel,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31')),
            CampaignTheme::of('#0b57d0', 'V'),
            4,
        );
        $campaign->publish(new DateTimeImmutable('2026-02-01'));
        $this->campaigns->save($campaign);
    }

    #[Test]
    public function view_campaign_returns_a_projection_with_the_participant_balance(): void
    {
        $balance = new ArrayAttemptBalance();
        $participantId = \Src\Domain\Participation\ParticipantId::generate();
        $balance->grant($this->id, $participantId, 3);

        $view = (new ViewCampaignHandler($this->campaigns, $balance, FixedClock::at('2026-06-01')))
            ->handle(new ViewCampaignQuery('view-me', (string) $participantId));

        self::assertSame('view-me', $view->slug);
        self::assertSame('wheel', $view->mechanic);
        self::assertTrue($view->acceptingAttempts);
        self::assertSame(3, $view->attemptsLeft);
    }

    #[Test]
    public function view_campaign_reports_not_found_for_an_unknown_slug(): void
    {
        $this->expectException(UseCaseException::class);

        (new ViewCampaignHandler($this->campaigns, new ArrayAttemptBalance(), FixedClock::at('2026-06-01')))
            ->handle(new ViewCampaignQuery('missing'));
    }

    #[Test]
    public function publish_campaign_moves_a_draft_forward_and_publishes_its_events(): void
    {
        $draftId = CampaignId::generate();
        $draft = Campaign::createDraft(
            $draftId,
            Slug::fromString('to-publish'),
            'Черновик',
            MechanicType::Wheel,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31')),
            CampaignTheme::default(),
            3,
        );
        $this->campaigns->save($draft);
        $events = new InMemoryEventPublisher();

        (new PublishCampaignHandler($this->campaigns, $events, FixedClock::at('2026-03-01')))
            ->handle(new PublishCampaignCommand((string) $draftId));

        self::assertSame(CampaignStatus::Active, $this->campaigns->byId($draftId)->status());
        self::assertSame(['campaign.published'], $events->publishedNames());
    }

    #[Test]
    public function publishing_an_already_active_campaign_is_a_conflict(): void
    {
        try {
            (new PublishCampaignHandler($this->campaigns, new InMemoryEventPublisher(), FixedClock::at('2026-03-01')))
                ->handle(new PublishCampaignCommand((string) $this->id));
            self::fail('Ожидался конфликт.');
        } catch (UseCaseException $e) {
            self::assertSame(409, $e->httpStatus);
        }
    }

    #[Test]
    public function add_prizes_rejects_a_non_positive_quantity(): void
    {
        $this->expectException(UseCaseException::class);

        (new AddPrizesHandler($this->campaigns, new InMemoryPrizePool()))
            ->handle(new AddPrizesCommand((string) $this->id, 'Приз', 0));
    }

    #[Test]
    public function upload_promo_codes_keeps_only_well_formed_unique_values(): void
    {
        $book = new InMemoryPromoCodeBook();

        $result = (new UploadPromoCodesHandler($this->campaigns, $book))->handle(
            new UploadPromoCodesCommand((string) $this->id, ['GOOD-1', 'GOOD-1', 'bad code!', 'GOOD-2']),
        );

        self::assertSame(['added' => 2, 'skipped' => 2], $result);
        self::assertSame(2, $book->total($this->id));
    }

    #[Test]
    public function campaign_stats_aggregate_counts_from_the_repositories(): void
    {
        $prizePool = new InMemoryPrizePool();
        $prizePool->addPrize($this->id, 'Приз', 7);
        $promoCodes = new InMemoryPromoCodeBook();
        $promoCodes->add($this->id, ['A', 'B', 'C']);

        $stats = (new CampaignStatsHandler(
            $this->campaigns,
            new InMemoryAttemptRepository(),
            new InMemoryParticipantRepository(),
            $prizePool,
            $promoCodes,
        ))->handle(new CampaignStatsQuery((string) $this->id));

        self::assertSame(0, $stats->attempts);
        self::assertSame(7, $stats->prizePoolLeft);
        self::assertSame(3, $stats->promoCodesLeft);
        self::assertCount(14, $stats->activity);
    }
}
