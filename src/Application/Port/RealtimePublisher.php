<?php

declare(strict_types=1);

namespace Src\Application\Port;

use Src\Domain\Campaign\Slug;
use Src\Domain\Leaderboard\LeaderboardEntry;
use Src\Domain\Participation\ParticipantId;

/**
 * Публикатор мгновенных сообщений в веб-сокеты (Centrifugo).
 */
interface RealtimePublisher
{
    /**
     * @param list<LeaderboardEntry> $entries
     */
    public function pushLeaderboard(Slug $slug, array $entries): void;

    /**
     * @param array<string, scalar|null> $data
     */
    public function pushToParticipant(ParticipantId $participantId, string $type, array $data): void;
}
