<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Mechanic\MechanicConfig;
use Src\Domain\Mechanic\MechanicConfigNotFound;
use Src\Domain\Mechanic\MechanicConfigRepository;

final class InMemoryMechanicConfigRepository implements MechanicConfigRepository
{
    /** @var array<string, MechanicConfig> */
    private array $configs = [];

    public function save(MechanicConfig $config): void
    {
        $this->configs[(string) $config->campaignId] = $config;
    }

    public function forCampaign(CampaignId $campaignId): MechanicConfig
    {
        return $this->configs[(string) $campaignId] ?? throw MechanicConfigNotFound::forCampaign((string) $campaignId);
    }

    public function deleteForCampaign(CampaignId $campaignId): void
    {
        unset($this->configs[(string) $campaignId]);
    }
}
