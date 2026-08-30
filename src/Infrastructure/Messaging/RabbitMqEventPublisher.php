<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Src\Application\Port\EventPublisher;
use Src\Domain\Event\DomainEvent;
use Throwable;

/**
 * Публикатор доменных событий в обменник RabbitMQ типа «topic».
 * Ключ маршрутизации — имя события (например «prize.awarded»).
 */
final class RabbitMqEventPublisher implements EventPublisher
{
    public function __construct(
        private readonly AMQPStreamConnection $connection,
        private readonly LoggerInterface $logger,
        private readonly string $exchange = 'zateya.domain-events',
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        if ($events === []) {
            return;
        }

        try {
            $channel = $this->connection->channel();
            $channel->exchange_declare($this->exchange, 'topic', false, true, false);

            foreach ($events as $event) {
                $body = json_encode([
                    'name' => $event->name(),
                    'occurred_at' => $event->occurredAt()->format(DATE_ATOM),
                    'payload' => $event->payload(),
                ], JSON_THROW_ON_ERROR);

                $channel->basic_publish(
                    new AMQPMessage($body, ['content_type' => 'application/json', 'delivery_mode' => 2]),
                    $this->exchange,
                    $event->name(),
                );
            }

            $channel->close();
        } catch (Throwable $e) {
            $this->logger->error('Не удалось опубликовать доменные события в RabbitMQ.', [
                'error' => $e->getMessage(),
                'events' => array_map(static fn (DomainEvent $event): string => $event->name(), $events),
            ]);
        }
    }
}
