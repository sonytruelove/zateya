<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

/**
 * Хранилище агрегата «Кампания». Реализация — в слое инфраструктуры.
 */
interface CampaignRepository
{
    public function save(Campaign $campaign): void;

    /** @throws CampaignNotFound */
    public function byId(CampaignId $id): Campaign;

    /** @throws CampaignNotFound */
    public function bySlug(Slug $slug): Campaign;

    public function existsWithSlug(Slug $slug): bool;

    public function delete(CampaignId $id): void;

    /**
     * @return list<Campaign>
     */
    public function all(): array;
}
