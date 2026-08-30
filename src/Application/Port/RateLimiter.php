<?php

declare(strict_types=1);

namespace Src\Application\Port;

/**
 * Ограничитель частоты запросов по ключу (Redis).
 */
interface RateLimiter
{
    /**
     * Регистрирует обращение. Возвращает false, если лимит за окно превышен.
     */
    public function hit(string $key, int $limit, int $windowSeconds): bool;
}
