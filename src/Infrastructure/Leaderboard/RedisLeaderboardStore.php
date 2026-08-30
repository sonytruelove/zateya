<?php

declare(strict_types=1);

namespace Src\Infrastructure\Leaderboard;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Leaderboard\LeaderboardEntry;
use Src\Domain\Leaderboard\LeaderboardStore;
use Src\Domain\Participation\ParticipantId;

/**
 * Рейтинг на упорядоченном множестве Redis (ZSET). Прибавление очков атомарно (ZINCRBY),
 * имена участников хранятся в спутниковом хэше.
 */
final class RedisLeaderboardStore implements LeaderboardStore
{
    public function __construct(
        private readonly RedisFactory $redis,
        private readonly string $connection = 'default',
    ) {
    }

    public function addScore(CampaignId $campaignId, ParticipantId $participantId, string $displayName, int $delta): void
    {
        $client = $this->client();
        $client->zincrby($this->scoreKey($campaignId), $delta, (string) $participantId);
        $client->hset($this->nameKey($campaignId), (string) $participantId, $displayName);
    }

    public function top(CampaignId $campaignId, int $limit): array
    {
        $raw = $this->client()->zrevrange($this->scoreKey($campaignId), 0, $limit - 1, ['withscores' => true]);
        $names = $this->client()->hgetall($this->nameKey($campaignId));

        $entries = [];
        $rank = 1;
        foreach ($raw as $participantId => $score) {
            $entries[] = new LeaderboardEntry($rank, (string) ($names[$participantId] ?? 'Участник'), (int) $score);
            $rank++;
        }

        return $entries;
    }

    public function positionOf(CampaignId $campaignId, ParticipantId $participantId): ?LeaderboardEntry
    {
        $client = $this->client();
        $rank = $client->zrevrank($this->scoreKey($campaignId), (string) $participantId);
        if ($rank === null || $rank === false) {
            return null;
        }

        $score = (int) $client->zscore($this->scoreKey($campaignId), (string) $participantId);
        $name = (string) ($client->hget($this->nameKey($campaignId), (string) $participantId) ?? 'Участник');

        return new LeaderboardEntry((int) $rank + 1, $name, $score);
    }

    public function clear(CampaignId $campaignId): void
    {
        $this->client()->del([$this->scoreKey($campaignId), $this->nameKey($campaignId)]);
    }

    private function client(): mixed
    {
        return $this->redis->connection($this->connection);
    }

    private function scoreKey(CampaignId $campaignId): string
    {
        return "zateya:lb:{$campaignId}:score";
    }

    private function nameKey(CampaignId $campaignId): string
    {
        return "zateya:lb:{$campaignId}:name";
    }
}
