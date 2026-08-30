<?php

declare(strict_types=1);

namespace Src\Domain\Reward;

use Src\Domain\Campaign\CampaignId;

/**
 * Призовой фонд кампании. Резервирование должно быть атомарным на уровне хранилища
 * (одновременные вызовы не могут увести остаток в минус).
 */
interface PrizePool
{
    public function addPrize(CampaignId $campaignId, string $title, int $quantity): PrizeId;

    /**
     * Атомарно уменьшает остаток на единицу и возвращает зарезервированный приз,
     * либо null, если свободных призов не осталось.
     */
    public function reserveOne(CampaignId $campaignId): ?Prize;

    public function releaseOne(PrizeId $prizeId): void;

    public function remaining(CampaignId $campaignId): int;

    public function total(CampaignId $campaignId): int;

    public function clearForCampaign(CampaignId $campaignId): void;
}
