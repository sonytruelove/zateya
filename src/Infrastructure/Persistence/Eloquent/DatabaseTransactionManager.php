<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\ConnectionInterface;
use Src\Application\Port\TransactionManager;

final class DatabaseTransactionManager implements TransactionManager
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function transactional(callable $work): mixed
    {
        return $this->db->transaction(static fn (): mixed => $work());
    }
}
