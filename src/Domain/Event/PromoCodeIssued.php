<?php

declare(strict_types=1);

namespace Src\Domain\Event;

use DateTimeImmutable;

final readonly class PromoCodeIssued implements DomainEvent
{
    public function __construct(
        public string $campaignId,
        public string $participantId,
        public string $code,
        public DateTimeImmutable $at,
    ) {
    }

    public function name(): string
    {
        return 'promo_code.issued';
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
            'code' => $this->code,
        ];
    }
}
