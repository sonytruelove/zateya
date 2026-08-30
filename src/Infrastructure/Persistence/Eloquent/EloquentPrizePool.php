<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\ConnectionInterface;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Reward\Prize;
use Src\Domain\Reward\PrizeId;
use Src\Domain\Reward\PrizePool;

/**
 * Призовой фонд поверх таблицы prizes. Резервирование атомарно:
 * условный UPDATE «remaining = remaining - 1 WHERE remaining > 0» списывает остаток
 * только если он положителен, поэтому параллельные вызовы не уводят его в минус.
 */
final class EloquentPrizePool implements PrizePool
{
    private const RESERVE_RETRIES = 5;

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function addPrize(CampaignId $campaignId, string $title, int $quantity): PrizeId
    {
        $id = PrizeId::generate();
        $now = date('Y-m-d H:i:sP');

        $this->db->table('prizes')->insert([
            'id' => (string) $id,
            'campaign_id' => (string) $campaignId,
            'title' => $title,
            'total_quantity' => $quantity,
            'remaining' => $quantity,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    public function reserveOne(CampaignId $campaignId): ?Prize
    {
        for ($attempt = 0; $attempt < self::RESERVE_RETRIES; $attempt++) {
            $candidate = $this->db->table('prizes')
                ->where('campaign_id', (string) $campaignId)
                ->where('remaining', '>', 0)
                ->orderByDesc('remaining')
                ->first(['id', 'title', 'remaining']);

            if ($candidate === null) {
                return null;
            }

            $applied = $this->db->table('prizes')
                ->where('id', $candidate->id)
                ->where('remaining', '>', 0)
                ->decrement('remaining');

            if ($applied === 1) {
                return new Prize(PrizeId::fromString((string) $candidate->id), (string) $candidate->title, (int) $candidate->remaining - 1);
            }
        }

        return null;
    }

    public function releaseOne(PrizeId $prizeId): void
    {
        $this->db->table('prizes')->where('id', (string) $prizeId)->increment('remaining');
    }

    public function remaining(CampaignId $campaignId): int
    {
        return (int) $this->db->table('prizes')->where('campaign_id', (string) $campaignId)->sum('remaining');
    }

    public function total(CampaignId $campaignId): int
    {
        return (int) $this->db->table('prizes')->where('campaign_id', (string) $campaignId)->sum('total_quantity');
    }

    public function clearForCampaign(CampaignId $campaignId): void
    {
        $this->db->table('prizes')->where('campaign_id', (string) $campaignId)->delete();
    }
}
