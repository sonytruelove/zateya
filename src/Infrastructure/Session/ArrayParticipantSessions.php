<?php

declare(strict_types=1);

namespace Src\Infrastructure\Session;

use Src\Application\Port\ParticipantSessions;
use Src\Domain\Participation\ParticipantId;

/**
 * Хранилище маркеров сессии в памяти процесса. Для тестов и локального запуска без Redis.
 */
final class ArrayParticipantSessions implements ParticipantSessions
{
    /** @var array<string, string> */
    private array $tokens = [];

    public function issue(ParticipantId $participantId, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $this->tokens[$token] = (string) $participantId;

        return $token;
    }

    public function resolve(string $token): ?ParticipantId
    {
        $value = $this->tokens[$token] ?? null;

        return $value === null ? null : ParticipantId::fromString($value);
    }
}
