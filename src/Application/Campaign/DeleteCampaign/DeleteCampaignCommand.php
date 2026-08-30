<?php

declare(strict_types=1);

namespace Src\Application\Campaign\DeleteCampaign;

final readonly class DeleteCampaignCommand
{
    public function __construct(public string $campaignId)
    {
    }
}
