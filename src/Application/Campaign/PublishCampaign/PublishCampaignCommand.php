<?php

declare(strict_types=1);

namespace Src\Application\Campaign\PublishCampaign;

final readonly class PublishCampaignCommand
{
    public function __construct(public string $campaignId)
    {
    }
}
