<?php

declare(strict_types=1);

namespace Src\Domain\Participation;

use DateTimeImmutable;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Mechanic\MechanicOutcome;
use Src\Domain\Shared\Uuid;

/**
 * Зафиксированная попытка участника: результат розыгрыша и, если был выигрыш,
 * выданные приз и промокод.
 */
final readonly class Attempt
{
    private function __construct(
        public string $id,
        public CampaignId $campaignId,
        public ParticipantId $participantId,
        public MechanicOutcome $outcome,
        public ?string $prizeId,
        public ?string $promoCode,
        public DateTimeImmutable $playedAt,
    ) {
    }

    public static function record(
        CampaignId $campaignId,
        ParticipantId $participantId,
        MechanicOutcome $outcome,
        ?string $prizeId,
        ?string $promoCode,
        DateTimeImmutable $playedAt,
    ): self {
        return new self(
            (string) Uuid::generate(),
            $campaignId,
            $participantId,
            $outcome,
            $prizeId,
            $promoCode,
            $playedAt,
        );
    }
}
