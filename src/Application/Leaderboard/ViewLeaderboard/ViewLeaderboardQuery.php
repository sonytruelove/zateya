<?php

declare(strict_types=1);

namespace Src\Application\Leaderboard\ViewLeaderboard;

final readonly class ViewLeaderboardQuery
{
    public function __construct(
        public string $slug,
        public ?string $participantId = null,
        public int $limit = 10,
    ) {
    }
}
