<?php

declare(strict_types=1);

namespace Src\Domain\Event;

use DateTimeImmutable;

final readonly class CampaignPublished implements DomainEvent
{
    public function __construct(
        public string $campaignId,
        public string $slug,
        public DateTimeImmutable $at,
    ) {
    }

    public function name(): string
    {
        return 'campaign.published';
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }

    public function payload(): array
    {
        return ['campaign_id' => $this->campaignId, 'slug' => $this->slug];
    }
}
