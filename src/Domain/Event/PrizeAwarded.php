<?php

declare(strict_types=1);

namespace Src\Domain\Event;

use DateTimeImmutable;

final readonly class PrizeAwarded implements DomainEvent
{
    public function __construct(
        public string $campaignId,
        public string $participantId,
        public string $prizeId,
        public string $prizeTitle,
        public DateTimeImmutable $at,
    ) {
    }

    public function name(): string
    {
        return 'prize.awarded';
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }

    public function payload(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'participant_id' => $this->participantId,
            'prize_id' => $this->prizeId,
            'prize_title' => $this->prizeTitle,
        ];
    }
}
