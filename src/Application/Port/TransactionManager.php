<?php

declare(strict_types=1);

namespace Src\Application\Port;

/**
 * Выполняет замыкание в транзакции хранилища: либо всё, либо ничего.
 */
interface TransactionManager
{
    /**
     * @template T
     * @param callable():T $work
     * @return T
     */
    public function transactional(callable $work): mixed;
}
