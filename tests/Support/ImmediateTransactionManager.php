<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Application\Port\TransactionManager;

final class ImmediateTransactionManager implements TransactionManager
{
    public function transactional(callable $work): mixed
    {
        return $work();
    }
}
