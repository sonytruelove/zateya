<?php

declare(strict_types=1);

namespace Src\Application\Port;

use Src\Domain\Participation\ParticipantId;

/**
 * Хранилище непрозрачных маркеров сессии участника (Redis).
 * Маркер выдаётся при открытии сессии и предъявляется при каждом ходе.
 */
interface ParticipantSessions
{
    public function issue(ParticipantId $participantId, int $ttlSeconds): string;

    public function resolve(string $token): ?ParticipantId;
}
