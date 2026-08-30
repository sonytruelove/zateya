<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Mechanic\MechanicConfig;
use Src\Domain\Mechanic\MechanicConfigNotFound;
use Src\Domain\Mechanic\MechanicConfigRepository;
use Src\Infrastructure\Persistence\Eloquent\Models\MechanicConfigModel;

final class EloquentMechanicConfigRepository implements MechanicConfigRepository
{
    public function save(MechanicConfig $config): void
    {
        MechanicConfigModel::query()->updateOrCreate(
            ['campaign_id' => (string) $config->campaignId],
            ['type' => $config->type->value, 'settings' => $config->settings],
        );
    }

    public function forCampaign(CampaignId $campaignId): MechanicConfig
    {
        $row = MechanicConfigModel::query()->find((string) $campaignId);
        if ($row === null) {
            throw MechanicConfigNotFound::forCampaign((string) $campaignId);
        }

        /** @var array<string, mixed> $settings */
        $settings = $row->settings;

        return new MechanicConfig($campaignId, MechanicType::from($row->type), $settings);
    }

    public function deleteForCampaign(CampaignId $campaignId): void
    {
        MechanicConfigModel::query()->whereKey((string) $campaignId)->delete();
    }
}
