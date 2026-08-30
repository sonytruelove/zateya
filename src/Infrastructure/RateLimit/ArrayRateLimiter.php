<?php

declare(strict_types=1);

namespace Src\Infrastructure\RateLimit;

use Src\Application\Port\RateLimiter;

/**
 * Ограничитель частоты в памяти процесса. Для тестов и локального запуска без Redis.
 */
final class ArrayRateLimiter implements RateLimiter
{
    /** @var array<string, array{count: int, expires_at: int}> */
    private array $buckets = [];

    public function hit(string $key, int $limit, int $windowSeconds): bool
    {
        $now = time();
        $bucket = $this->buckets[$key] ?? null;
        if ($bucket === null || $bucket['expires_at'] <= $now) {
            $this->buckets[$key] = ['count' => 1, 'expires_at' => $now + $windowSeconds];

            return true;
        }

        $this->buckets[$key]['count']++;

        return $this->buckets[$key]['count'] <= $limit;
    }
}
