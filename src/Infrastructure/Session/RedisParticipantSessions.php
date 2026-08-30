<?php

declare(strict_types=1);

namespace Src\Infrastructure\Session;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Src\Application\Port\ParticipantSessions;
use Src\Domain\Participation\ParticipantId;

final class RedisParticipantSessions implements ParticipantSessions
{
    public function __construct(
        private readonly RedisFactory $redis,
        private readonly string $connection = 'default',
    ) {
    }

    public function issue(ParticipantId $participantId, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $this->redis->connection($this->connection)->setex($this->key($token), $ttlSeconds, (string) $participantId);

        return $token;
    }

    public function resolve(string $token): ?ParticipantId
    {
        $value = $this->redis->connection($this->connection)->get($this->key($token));

        return $value === null ? null : ParticipantId::fromString((string) $value);
    }

    private function key(string $token): string
    {
        return "zateya:session:{$token}";
    }
}
