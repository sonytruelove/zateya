<?php

declare(strict_types=1);

namespace Src\Infrastructure\Messaging;

use Src\Application\Port\EventPublisher;
use Src\Domain\Event\DomainEvent;

/**
 * Публикатор событий в память процесса. Используется в тестах для проверки,
 * какие события были порождены сценарием.
 */
final class InMemoryEventPublisher implements EventPublisher
{
    /** @var list<DomainEvent> */
    private array $published = [];

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->published[] = $event;
        }
    }

    /**
     * @return list<DomainEvent>
     */
    public function published(): array
    {
        return $this->published;
    }

    /**
     * @return list<string>
     */
    public function publishedNames(): array
    {
        return array_map(static fn (DomainEvent $event): string => $event->name(), $this->published);
    }
}
