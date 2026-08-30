<?php

declare(strict_types=1);

namespace Src\Infrastructure\RateLimit;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Src\Application\Port\RateLimiter;

final class RedisRateLimiter implements RateLimiter
{
    public function __construct(
        private readonly RedisFactory $redis,
        private readonly string $connection = 'default',
    ) {
    }

    public function hit(string $key, int $limit, int $windowSeconds): bool
    {
        $client = $this->redis->connection($this->connection);
        $redisKey = "zateya:rl:{$key}";
        $hits = (int) $client->incr($redisKey);
        if ($hits === 1) {
            $client->expire($redisKey, $windowSeconds);
        }

        return $hits <= $limit;
    }
}
