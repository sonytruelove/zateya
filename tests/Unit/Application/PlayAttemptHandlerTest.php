<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Application\Participation\PlayAttempt\PlayAttemptCommand;
use Src\Application\Participation\PlayAttempt\PlayAttemptHandler;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Domain\Mechanic\MechanicConfig;
use Src\Domain\Mechanic\MechanicFactory;
use Src\Domain\Participation\Channel;
use Src\Domain\Participation\ChannelIdentity;
use Src\Domain\Participation\Participant;
use Src\Domain\Participation\ParticipantId;
use Src\Infrastructure\Messaging\InMemoryEventPublisher;
use Tests\Support\FixedClock;
use Tests\Support\ImmediateTransactionManager;
use Tests\Support\InMemoryAttemptRepository;
use Tests\Support\InMemoryCampaignRepository;
use Tests\Support\InMemoryMechanicConfigRepository;
use Tests\Support\InMemoryParticipantRepository;
use Tests\Support\InMemoryPrizePool;
use Tests\Support\InMemoryPromoCodeBook;
use Tests\Support\RecordingRealtimePublisher;
use Tests\Support\SequenceRandomSource;

final class PlayAttemptHandlerTest extends TestCase
{
    private InMemoryCampaignRepository $campaigns;
    private InMemoryMechanicConfigRepository $configs;
    private InMemoryParticipantRepository $participants;
    private InMemoryAttemptRepository $attempts;
    private InMemoryPrizePool $prizePool;
    private InMemoryPromoCodeBook $promoCodes;
    private \Src\Infrastructure\Leaderboard\ArrayLeaderboardStore $leaderboard;
    private \Src\Infrastructure\Balance\ArrayAttemptBalance $balance;
    private InMemoryEventPublisher $events;
    private RecordingRealtimePublisher $realtime;
    private CampaignId $campaignId;
    private ParticipantId $participantId;

    protected function setUp(): void
    {
        $this->campaigns = new InMemoryCampaignRepository();
        $this->configs = new InMemoryMechanicConfigRepository();
        $this->participants = new InMemoryParticipantRepository();
        $this->attempts = new InMemoryAttemptRepository();
        $this->prizePool = new InMemoryPrizePool();
        $this->promoCodes = new InMemoryPromoCodeBook();
        $this->leaderboard = new \Src\Infrastructure\Leaderboard\ArrayLeaderboardStore();
        $this->balance = new \Src\Infrastructure\Balance\ArrayAttemptBalance();
        $this->events = new InMemoryEventPublisher();
        $this->realtime = new RecordingRealtimePublisher();

        $this->campaignId = CampaignId::generate();
        $campaign = Campaign::createDraft(
            $this->campaignId,
            Slug::fromString('demo-wheel'),
            'Демо-колесо',
            MechanicType::Wheel,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31')),
            CampaignTheme::default(),
            3,
        );
        $campaign->publish(new DateTimeImmutable('2026-02-01'));
        $this->campaigns->save($campaign);

        $this->configs->save(new MechanicConfig($this->campaignId, MechanicType::Wheel, [
            'sectors' => [
                ['label' => 'Пусто', 'weight' => 50, 'winning' => false, 'points' => 1],
                ['label' => 'Приз', 'weight' => 50, 'winning' => true, 'points' => 30],
            ],
        ]));

        $this->participantId = ParticipantId::generate();
        $this->participants->save(Participant::register(
            $this->participantId,
            $this->campaignId,
            ChannelIdentity::of(Channel::Web, 'browser-1'),
            'Игрок',
            new DateTimeImmutable('2026-02-01'),
        ));
        $this->balance->grant($this->campaignId, $this->participantId, 3);
    }

    #[Test]
    public function a_winning_attempt_reserves_a_prize_issues_a_code_and_emits_events(): void
    {
        $this->prizePool->addPrize($this->campaignId, 'Подарок', 5);
        $this->promoCodes->add($this->campaignId, ['CODE-1', 'CODE-2']);

        $result = $this->handler(new SequenceRandomSource(90))->handle($this->command());

        self::assertTrue($result->won);
        self::assertSame('Подарок', $result->prizeTitle);
        self::assertSame('CODE-1', $result->promoCode);
        self::assertSame(2, $result->attemptsLeft);
        self::assertSame(4, $this->prizePool->remaining($this->campaignId));
        self::assertSame(1, $this->promoCodes->remaining($this->campaignId));
        self::assertSame(
            ['attempt.played', 'prize.awarded', 'promo_code.issued'],
            $this->events->publishedNames(),
        );
        self::assertContains('prize_awarded', $this->realtime->typesForParticipant());
    }

    #[Test]
    public function a_win_with_an_empty_prize_pool_gives_a_consolation_result(): void
    {
        $result = $this->handler(new SequenceRandomSource(90))->handle($this->command());

        self::assertTrue($result->won);
        self::assertNull($result->prizeTitle);
        self::assertNull($result->promoCode);
        self::assertSame(['attempt.played'], $this->events->publishedNames());
    }

    #[Test]
    public function a_losing_attempt_still_records_score_and_updates_the_leaderboard(): void
    {
        $result = $this->handler(new SequenceRandomSource(10))->handle($this->command());

        self::assertFalse($result->won);
        self::assertSame(1, $result->score);
        self::assertSame(1, $this->attempts->countForCampaign($this->campaignId));
        self::assertNotSame([], $this->leaderboard->top($this->campaignId, 10));
    }

    #[Test]
    public function running_out_of_attempts_is_rejected_and_does_not_spend_a_prize(): void
    {
        $this->prizePool->addPrize($this->campaignId, 'Подарок', 5);
        $handler = $this->handler(new SequenceRandomSource(90));

        $handler->handle($this->command());
        $handler->handle($this->command());
        $handler->handle($this->command());

        try {
            $handler->handle($this->command());
            self::fail('Ожидалось исключение об исчерпании попыток.');
        } catch (UseCaseException $e) {
            self::assertSame('no_attempts_left', $e->errorCode);
        }

        self::assertSame(2, $this->prizePool->remaining($this->campaignId));
        self::assertSame(0, $this->balance->remaining($this->campaignId, $this->participantId));
    }

    #[Test]
    public function an_attempt_on_a_campaign_that_is_not_accepting_is_rejected(): void
    {
        $handler = new PlayAttemptHandler(
            $this->campaigns,
            $this->configs,
            new MechanicFactory(),
            $this->participants,
            $this->attempts,
            $this->balance,
            $this->prizePool,
            $this->promoCodes,
            $this->leaderboard,
            $this->events,
            $this->realtime,
            new ImmediateTransactionManager(),
            new SequenceRandomSource(90),
            FixedClock::at('2027-01-01'),
        );

        $this->expectException(UseCaseException::class);
        $handler->handle($this->command());
    }

    private function handler(SequenceRandomSource $random): PlayAttemptHandler
    {
        return new PlayAttemptHandler(
            $this->campaigns,
            $this->configs,
            new MechanicFactory(),
            $this->participants,
            $this->attempts,
            $this->balance,
            $this->prizePool,
            $this->promoCodes,
            $this->leaderboard,
            $this->events,
            $this->realtime,
            new ImmediateTransactionManager(),
            $random,
            FixedClock::at('2026-06-01'),
        );
    }

    private function command(): PlayAttemptCommand
    {
        return new PlayAttemptCommand('demo-wheel', (string) $this->participantId, []);
    }
}
