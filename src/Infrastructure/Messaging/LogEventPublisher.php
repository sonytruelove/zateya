<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging;

use Psr\Log\LoggerInterface;
use Src\Application\Port\EventPublisher;
use Src\Domain\Event\DomainEvent;

/**
 * Публикатор событий в журнал приложения. Активен, когда RabbitMQ выключен
 * (локальный запуск, тесты).
 */
final class LogEventPublisher implements EventPublisher
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->logger->info("Доменное событие: {$event->name()}", $event->payload());
        }
    }
}
