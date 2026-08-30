<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic;

use Src\Domain\Campaign\CampaignId;

interface MechanicConfigRepository
{
    public function save(MechanicConfig $config): void;

    /** @throws MechanicConfigNotFound */
    public function forCampaign(CampaignId $campaignId): MechanicConfig;

    public function deleteForCampaign(CampaignId $campaignId): void;
}
