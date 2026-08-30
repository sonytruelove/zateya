<?php

declare(strict_types=1);

namespace Src\Domain\Leaderboard;

final readonly class LeaderboardEntry
{
    public function __construct(
        public int $rank,
        public string $displayName,
        public int $score,
    ) {
    }
}
