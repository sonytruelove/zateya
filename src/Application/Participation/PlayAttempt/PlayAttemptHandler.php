<?php

declare(strict_types=1);

namespace Src\Application\Participation\PlayAttempt;

use DateTimeImmutable;
use Src\Application\Port\AttemptBalance;
use Src\Application\Port\EventPublisher;
use Src\Application\Port\RealtimePublisher;
use Src\Application\Port\TransactionManager;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\Slug;
use Src\Domain\Event\AttemptPlayed;
use Src\Domain\Event\DomainEvent;
use Src\Domain\Event\PrizeAwarded;
use Src\Domain\Event\PromoCodeIssued;
use Src\Domain\Leaderboard\LeaderboardStore;
use Src\Domain\Mechanic\Mechanic;
use Src\Domain\Mechanic\MechanicConfigNotFound;
use Src\Domain\Mechanic\MechanicConfigRepository;
use Src\Domain\Mechanic\MechanicFactory;
use Src\Domain\Mechanic\MechanicOutcome;
use Src\Domain\Mechanic\RandomSource;
use Src\Domain\Participation\Attempt;
use Src\Domain\Participation\AttemptRepository;
use Src\Domain\Participation\Participant;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Participation\ParticipantNotFound;
use Src\Domain\Participation\ParticipantRepository;
use Src\Domain\Reward\Prize;
use Src\Domain\Reward\PrizePool;
use Src\Domain\Reward\PromoCode;
use Src\Domain\Reward\PromoCodeBook;
use Src\Domain\Shared\Clock;
use Src\Domain\Shared\DomainException;
use Throwable;

/**
 * Центральный сценарий: розыгрыш одной попытки участника в механике кампании.
 * Списание попытки, резервирование приза и выдача промокода — атомарны на уровне хранилищ.
 */
final readonly class PlayAttemptHandler
{
    private const LEADERBOARD_SIZE = 10;

    public function __construct(
        private CampaignRepository $campaigns,
        private MechanicConfigRepository $mechanicConfigs,
        private MechanicFactory $mechanicFactory,
        private ParticipantRepository $participants,
        private AttemptRepository $attempts,
        private AttemptBalance $balance,
        private PrizePool $prizePool,
        private PromoCodeBook $promoCodes,
        private LeaderboardStore $leaderboard,
        private EventPublisher $events,
        private RealtimePublisher $realtime,
        private TransactionManager $tx,
        private RandomSource $random,
        private Clock $clock,
    ) {
    }

    public function handle(PlayAttemptCommand $command): PlayResult
    {
        $now = $this->clock->now();
        $campaign = $this->loadAcceptingCampaign($command->campaignSlug, $now);
        $participant = $this->loadParticipant($command->participantId);
        $this->assertParticipantBelongs($campaign, $participant);

        $mechanic = $this->buildMechanic($campaign);
        $this->validatePayload($mechanic, $command->payload);

        if (!$this->balance->consumeOne($campaign->id, $participant->id)) {
            throw UseCaseException::conflict('no_attempts_left', 'Попытки на эту кампанию закончились.');
        }

        $outcome = $this->playSafely($mechanic, $command->payload, $campaign, $participant);
        [$prize, $promoCode] = $this->awardIfWon($outcome, $campaign, $participant);
        $attempt = $this->persistAttempt($campaign, $participant, $outcome, $prize, $promoCode, $now);
        $this->announce($campaign, $participant, $outcome, $prize, $promoCode, $attempt->id, $now);

        return new PlayResult(
            won: $outcome->won,
            score: $outcome->score,
            detail: $outcome->detail,
            attemptsLeft: $this->balance->remaining($campaign->id, $participant->id),
            prizeTitle: $prize?->title,
            promoCode: $promoCode?->code,
        );
    }

    private function loadAcceptingCampaign(string $slug, DateTimeImmutable $now): Campaign
    {
        try {
            $campaign = $this->campaigns->bySlug(Slug::fromString($slug));
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        if (!$campaign->isAcceptingAttempts($now)) {
            throw UseCaseException::conflict('campaign_not_accepting', 'Кампания сейчас не принимает попытки.');
        }

        return $campaign;
    }

    private function loadParticipant(string $participantId): Participant
    {
        try {
            return $this->participants->byId(ParticipantId::fromString($participantId));
        } catch (ParticipantNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }
    }

    private function assertParticipantBelongs(Campaign $campaign, Participant $participant): void
    {
        if (!$participant->campaignId->equals($campaign->id)) {
            throw UseCaseException::forbidden('Участник зарегистрирован в другой кампании.');
        }
    }

    private function buildMechanic(Campaign $campaign): Mechanic
    {
        try {
            return $this->mechanicFactory->fromConfig($this->mechanicConfigs->forCampaign($campaign->id));
        } catch (MechanicConfigNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        } catch (DomainException $e) {
            throw UseCaseException::unprocessable('mechanic_unavailable', $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(Mechanic $mechanic, array $payload): void
    {
        try {
            $mechanic->validate($payload);
        } catch (DomainException $e) {
            throw UseCaseException::unprocessable('invalid_move', $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function playSafely(Mechanic $mechanic, array $payload, Campaign $campaign, Participant $participant): MechanicOutcome
    {
        try {
            return $mechanic->play($payload, $this->random);
        } catch (Throwable $e) {
            $this->balance->refundOne($campaign->id, $participant->id);
            throw $e;
        }
    }

    /**
     * @return array{0: ?Prize, 1: ?PromoCode}
     */
    private function awardIfWon(MechanicOutcome $outcome, Campaign $campaign, Participant $participant): array
    {
        if (!$outcome->won) {
            return [null, null];
        }

        $prize = $this->prizePool->reserveOne($campaign->id);
        if ($prize === null) {
            return [null, null];
        }

        $promoCode = $this->promoCodes->issueNext($campaign->id, $participant->id);

        return [$prize, $promoCode];
    }

    private function persistAttempt(
        Campaign $campaign,
        Participant $participant,
        MechanicOutcome $outcome,
        ?Prize $prize,
        ?PromoCode $promoCode,
        DateTimeImmutable $now,
    ): Attempt {
        return $this->tx->transactional(function () use ($campaign, $participant, $outcome, $prize, $promoCode, $now): Attempt {
            $attempt = Attempt::record(
                $campaign->id,
                $participant->id,
                $outcome,
                $prize !== null ? (string) $prize->id : null,
                $promoCode?->code,
                $now,
            );
            $this->attempts->save($attempt);

            return $attempt;
        });
    }

    private function announce(
        Campaign $campaign,
        Participant $participant,
        MechanicOutcome $outcome,
        ?Prize $prize,
        ?PromoCode $promoCode,
        string $attemptId,
        DateTimeImmutable $now,
    ): void {
        $this->events->publish(...$this->collectEvents($campaign, $participant, $outcome, $prize, $promoCode, $attemptId, $now));

        $this->leaderboard->addScore($campaign->id, $participant->id, $participant->displayName(), $outcome->score);
        $this->realtime->pushLeaderboard($campaign->slug, $this->leaderboard->top($campaign->id, self::LEADERBOARD_SIZE));

        if ($prize !== null) {
            $this->realtime->pushToParticipant($participant->id, 'prize_awarded', [
                'prize_title' => $prize->title,
                'promo_code' => $promoCode?->code,
            ]);
        }
    }

    /**
     * @return list<DomainEvent>
     */
    private function collectEvents(
        Campaign $campaign,
        Participant $participant,
        MechanicOutcome $outcome,
        ?Prize $prize,
        ?PromoCode $promoCode,
        string $attemptId,
        DateTimeImmutable $now,
    ): array {
        $campaignId = (string) $campaign->id;
        $participantId = (string) $participant->id;

        $events = [new AttemptPlayed($attemptId, $campaignId, $participantId, $outcome->won, $outcome->score, $now)];

        if ($prize !== null) {
            $events[] = new PrizeAwarded($campaignId, $participantId, (string) $prize->id, $prize->title, $now);
        }

        if ($promoCode !== null) {
            $events[] = new PromoCodeIssued($campaignId, $participantId, $promoCode->code, $now);
        }

        return $events;
    }
}
