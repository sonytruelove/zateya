<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Reward\Prize;
use Src\Domain\Reward\PrizeId;
use Src\Domain\Reward\PrizePool;

final class InMemoryPrizePool implements PrizePool
{
    /** @var array<string, array{campaign: string, title: string, total: int, remaining: int}> */
    private array $prizes = [];

    public function addPrize(CampaignId $campaignId, string $title, int $quantity): PrizeId
    {
        $id = PrizeId::generate();
        $this->prizes[(string) $id] = [
            'campaign' => (string) $campaignId,
            'title' => $title,
            'total' => $quantity,
            'remaining' => $quantity,
        ];

        return $id;
    }

    public function reserveOne(CampaignId $campaignId): ?Prize
    {
        foreach ($this->prizes as $id => $prize) {
            if ($prize['campaign'] === (string) $campaignId && $prize['remaining'] > 0) {
                $this->prizes[$id]['remaining']--;

                return new Prize(PrizeId::fromString($id), $prize['title'], $this->prizes[$id]['remaining']);
            }
        }

        return null;
    }

    public function releaseOne(PrizeId $prizeId): void
    {
        if (isset($this->prizes[(string) $prizeId])) {
            $this->prizes[(string) $prizeId]['remaining']++;
        }
    }

    public function remaining(CampaignId $campaignId): int
    {
        return $this->sum($campaignId, 'remaining');
    }

    public function total(CampaignId $campaignId): int
    {
        return $this->sum($campaignId, 'total');
    }

    public function clearForCampaign(CampaignId $campaignId): void
    {
        foreach ($this->prizes as $id => $prize) {
            if ($prize['campaign'] === (string) $campaignId) {
                unset($this->prizes[$id]);
            }
        }
    }

    private function sum(CampaignId $campaignId, string $field): int
    {
        $total = 0;
        foreach ($this->prizes as $prize) {
            if ($prize['campaign'] === (string) $campaignId) {
                $total += $prize[$field];
            }
        }

        return $total;
    }
}
