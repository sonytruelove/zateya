<?php

declare(strict_types=1);

namespace Src\Application\Campaign\CreateCampaign;

final readonly class CampaignCreated
{
    public function __construct(
        public string $campaignId,
        public string $slug,
    ) {
    }
}
