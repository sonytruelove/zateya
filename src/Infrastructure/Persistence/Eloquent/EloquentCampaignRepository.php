<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\Slug;
use Src\Infrastructure\Persistence\Eloquent\Models\CampaignModel;

final class EloquentCampaignRepository implements CampaignRepository
{
    public function save(Campaign $campaign): void
    {
        $period = $campaign->period();

        CampaignModel::query()->updateOrCreate(
            ['id' => (string) $campaign->id],
            [
                'slug' => (string) $campaign->slug,
                'title' => $campaign->title(),
                'mechanic' => $campaign->mechanic->value,
                'status' => $campaign->status()->value,
                'starts_at' => $period->startsAt->format('Y-m-d H:i:sP'),
                'ends_at' => $period->endsAt->format('Y-m-d H:i:sP'),
                'color_hex' => $campaign->theme()->colorHex,
                'emoji' => $campaign->theme()->emoji,
                'attempts_per_participant' => $campaign->attemptsPerParticipant(),
            ],
        );
    }

    public function byId(CampaignId $id): Campaign
    {
        $row = CampaignModel::query()->find((string) $id);
        if ($row === null) {
            throw CampaignNotFound::withId($id);
        }

        return $this->hydrate($row);
    }

    public function bySlug(Slug $slug): Campaign
    {
        $row = CampaignModel::query()->where('slug', (string) $slug)->first();
        if ($row === null) {
            throw CampaignNotFound::withSlug($slug);
        }

        return $this->hydrate($row);
    }

    public function existsWithSlug(Slug $slug): bool
    {
        return CampaignModel::query()->where('slug', (string) $slug)->exists();
    }

    public function delete(CampaignId $id): void
    {
        CampaignModel::query()->whereKey((string) $id)->delete();
    }

    public function all(): array
    {
        return array_values(
            CampaignModel::query()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (CampaignModel $row): Campaign => $this->hydrate($row))
                ->all(),
        );
    }

    private function hydrate(CampaignModel $row): Campaign
    {
        return Campaign::fromState([
            'id' => $row->id,
            'slug' => $row->slug,
            'title' => $row->title,
            'mechanic' => $row->mechanic,
            'starts_at' => $row->starts_at,
            'ends_at' => $row->ends_at,
            'color_hex' => $row->color_hex,
            'emoji' => $row->emoji,
            'attempts' => (int) $row->attempts_per_participant,
            'status' => $row->status,
        ]);
    }
}
