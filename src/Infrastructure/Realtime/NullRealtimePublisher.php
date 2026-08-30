<?php

declare(strict_types=1);

namespace Src\Infrastructure\Realtime;

use Src\Application\Port\RealtimePublisher;
use Src\Domain\Campaign\Slug;
use Src\Domain\Participation\ParticipantId;

/**
 * Заглушка публикатора реального времени. Активна, когда Centrifugo выключен
 * (локальный запуск, тесты).
 */
final class NullRealtimePublisher implements RealtimePublisher
{
    public function pushLeaderboard(Slug $slug, array $entries): void
    {
    }

    public function pushToParticipant(ParticipantId $participantId, string $type, array $data): void
    {
    }
}
