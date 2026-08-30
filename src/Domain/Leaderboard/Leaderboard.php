<?php

declare(strict_types=1);

namespace Src\Domain\Leaderboard;

/**
 * Срез рейтинга: верхние позиции и позиция текущего участника (если известна).
 */
final readonly class Leaderboard
{
    /**
     * @param list<LeaderboardEntry> $entries
     */
    public function __construct(
        public array $entries,
        public ?LeaderboardEntry $me,
    ) {
    }
}
