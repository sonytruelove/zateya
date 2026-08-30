<?php

declare(strict_types=1);

namespace Src\Application\Port;

use Src\Domain\Event\DomainEvent;

/**
 * Публикатор доменных событий во внешний брокер (RabbitMQ).
 */
interface EventPublisher
{
    public function publish(DomainEvent ...$events): void;
}
