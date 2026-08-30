<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\Slug;

final class InMemoryCampaignRepository implements CampaignRepository
{
    /** @var array<string, Campaign> */
    private array $byId = [];

    public function save(Campaign $campaign): void
    {
        $this->byId[(string) $campaign->id] = $campaign;
    }

    public function byId(CampaignId $id): Campaign
    {
        return $this->byId[(string) $id] ?? throw CampaignNotFound::withId($id);
    }

    public function bySlug(Slug $slug): Campaign
    {
        foreach ($this->byId as $campaign) {
            if ($campaign->slug->equals($slug)) {
                return $campaign;
            }
        }

        throw CampaignNotFound::withSlug($slug);
    }

    public function existsWithSlug(Slug $slug): bool
    {
        foreach ($this->byId as $campaign) {
            if ($campaign->slug->equals($slug)) {
                return true;
            }
        }

        return false;
    }

    public function delete(CampaignId $id): void
    {
        unset($this->byId[(string) $id]);
    }

    public function all(): array
    {
        return array_values($this->byId);
    }
}
