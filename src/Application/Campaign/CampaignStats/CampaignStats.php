<?php

declare(strict_types=1);

namespace Src\Application\Campaign\CampaignStats;

final readonly class CampaignStats
{
    /**
     * @param list<int> $activity
     */
    public function __construct(
        public int $attempts,
        public int $participants,
        public int $winners,
        public int $prizePoolLeft,
        public int $promoCodesLeft,
        public array $activity,
    ) {
    }
}
