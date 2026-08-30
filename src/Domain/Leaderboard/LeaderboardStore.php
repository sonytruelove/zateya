<?php

declare(strict_types=1);

namespace Src\Domain\Leaderboard;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;

/**
 * Хранилище рейтинга. Реализация — упорядоченное множество Redis (ZSET):
 * прибавление очков атомарно (ZINCRBY), чтение верхушки — за один запрос.
 */
interface LeaderboardStore
{
    public function addScore(
        CampaignId $campaignId,
        ParticipantId $participantId,
        string $displayName,
        int $delta,
    ): void;

    /**
     * @return list<LeaderboardEntry>
     */
    public function top(CampaignId $campaignId, int $limit): array;

    public function positionOf(CampaignId $campaignId, ParticipantId $participantId): ?LeaderboardEntry;

    public function clear(CampaignId $campaignId): void;
}
