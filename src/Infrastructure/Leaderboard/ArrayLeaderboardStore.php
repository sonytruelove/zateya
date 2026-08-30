<?php

declare(strict_types=1);

namespace Src\Infrastructure\Leaderboard;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Leaderboard\LeaderboardEntry;
use Src\Domain\Leaderboard\LeaderboardStore;
use Src\Domain\Participation\ParticipantId;

/**
 * Рейтинг в памяти процесса. Используется в тестах и при локальном запуске без Redis.
 *
 * @phpstan-type Row array{name: string, score: int}
 */
final class ArrayLeaderboardStore implements LeaderboardStore
{
    /** @var array<string, array<string, array{name: string, score: int}>> */
    private array $boards = [];

    public function addScore(CampaignId $campaignId, ParticipantId $participantId, string $displayName, int $delta): void
    {
        $board = &$this->boards[(string) $campaignId];
        $pid = (string) $participantId;
        $current = $board[$pid]['score'] ?? 0;
        $board[$pid] = ['name' => $displayName, 'score' => $current + $delta];
    }

    public function top(CampaignId $campaignId, int $limit): array
    {
        $rows = $this->sorted($campaignId);
        $entries = [];
        $rank = 1;
        foreach (array_slice($rows, 0, $limit, true) as $row) {
            $entries[] = new LeaderboardEntry($rank, $row['name'], $row['score']);
            $rank++;
        }

        return $entries;
    }

    public function positionOf(CampaignId $campaignId, ParticipantId $participantId): ?LeaderboardEntry
    {
        $rows = $this->sorted($campaignId);
        $pid = (string) $participantId;
        $rank = 1;
        foreach ($rows as $id => $row) {
            if ($id === $pid) {
                return new LeaderboardEntry($rank, $row['name'], $row['score']);
            }
            $rank++;
        }

        return null;
    }

    public function clear(CampaignId $campaignId): void
    {
        unset($this->boards[(string) $campaignId]);
    }

    /**
     * @return array<string, array{name: string, score: int}>
     */
    private function sorted(CampaignId $campaignId): array
    {
        $rows = $this->boards[(string) $campaignId] ?? [];
        uasort($rows, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $rows;
    }
}
