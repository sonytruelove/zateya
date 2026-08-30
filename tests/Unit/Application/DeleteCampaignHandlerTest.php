<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Application\Campaign\DeleteCampaign\DeleteCampaignCommand;
use Src\Application\Campaign\DeleteCampaign\DeleteCampaignHandler;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Domain\Mechanic\MechanicConfig;
use Src\Domain\Mechanic\MechanicOutcome;
use Src\Domain\Participation\Attempt;
use Src\Domain\Participation\Channel;
use Src\Domain\Participation\ChannelIdentity;
use Src\Domain\Participation\Participant;
use Src\Domain\Participation\ParticipantId;
use Src\Infrastructure\Balance\ArrayAttemptBalance;
use Src\Infrastructure\Leaderboard\ArrayLeaderboardStore;
use Tests\Support\ImmediateTransactionManager;
use Tests\Support\InMemoryAttemptRepository;
use Tests\Support\InMemoryCampaignRepository;
use Tests\Support\InMemoryMechanicConfigRepository;
use Tests\Support\InMemoryParticipantRepository;
use Tests\Support\InMemoryPrizePool;
use Tests\Support\InMemoryPromoCodeBook;

final class DeleteCampaignHandlerTest extends TestCase
{
    #[Test]
    public function it_removes_the_campaign_and_every_related_record_and_redis_trace(): void
    {
        $campaigns = new InMemoryCampaignRepository();
        $configs = new InMemoryMechanicConfigRepository();
        $attempts = new InMemoryAttemptRepository();
        $participants = new InMemoryParticipantRepository();
        $prizePool = new InMemoryPrizePool();
        $promoCodes = new InMemoryPromoCodeBook();
        $leaderboard = new ArrayLeaderboardStore();
        $balance = new ArrayAttemptBalance();

        $id = CampaignId::generate();
        $campaigns->save(Campaign::createDraft(
            $id,
            Slug::fromString('to-be-deleted'),
            'На удаление',
            MechanicType::Wheel,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31')),
            CampaignTheme::default(),
            3,
        ));
        $configs->save(new MechanicConfig($id, MechanicType::Wheel, ['sectors' => [['label' => 'X', 'weight' => 1, 'winning' => true, 'points' => 1]]]));

        $participant = Participant::register(ParticipantId::generate(), $id, ChannelIdentity::of(Channel::Web, 'u1'), 'Игрок', new DateTimeImmutable());
        $participants->save($participant);
        $attempts->save(Attempt::record($id, $participant->id, MechanicOutcome::win(10, 'ok'), null, null, new DateTimeImmutable()));
        $prizePool->addPrize($id, 'Приз', 3);
        $promoCodes->add($id, ['A', 'B']);
        $leaderboard->addScore($id, $participant->id, 'Игрок', 10);
        $balance->grant($id, $participant->id, 5);

        $handler = new DeleteCampaignHandler(
            $campaigns,
            $configs,
            $attempts,
            $participants,
            $prizePool,
            $promoCodes,
            $leaderboard,
            $balance,
            new ImmediateTransactionManager(),
        );
        $handler->handle(new DeleteCampaignCommand((string) $id));

        self::assertFalse($campaigns->existsWithSlug(Slug::fromString('to-be-deleted')));
        self::assertSame(0, $attempts->countForCampaign($id));
        try {
            $configs->forCampaign($id);
            self::fail('Конфигурация механики должна быть удалена вместе с кампанией.');
        } catch (\Src\Domain\Mechanic\MechanicConfigNotFound) {
            self::assertTrue(true);
        }
        self::assertSame(0, $participants->countForCampaign($id));
        self::assertSame(0, $prizePool->total($id));
        self::assertSame(0, $promoCodes->total($id));
        self::assertSame([], $leaderboard->top($id, 10));
        self::assertSame(0, $balance->remaining($id, $participant->id));
    }

    #[Test]
    public function deleting_a_missing_campaign_reports_not_found(): void
    {
        $handler = new DeleteCampaignHandler(
            new InMemoryCampaignRepository(),
            new InMemoryMechanicConfigRepository(),
            new InMemoryAttemptRepository(),
            new InMemoryParticipantRepository(),
            new InMemoryPrizePool(),
            new InMemoryPromoCodeBook(),
            new ArrayLeaderboardStore(),
            new ArrayAttemptBalance(),
            new ImmediateTransactionManager(),
        );

        $this->expectException(UseCaseException::class);
        $handler->handle(new DeleteCampaignCommand((string) CampaignId::generate()));
    }
}
