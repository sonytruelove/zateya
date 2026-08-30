<?php

declare(strict_types=1);

namespace Src\Application\Campaign\CampaignStats;

final readonly class CampaignStatsQuery
{
    public function __construct(
        public string $campaignId,
        public int $activityDays = 14,
    ) {
    }
}
