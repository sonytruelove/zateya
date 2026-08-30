<?php

declare(strict_types=1);

namespace Src\Domain\Event;

use DateTimeImmutable;

/**
 * Доменное событие — факт, произошедший в предметной области.
 * Публикуется в брокер сообщений слоем инфраструктуры.
 */
interface DomainEvent
{
    public function name(): string;

    public function occurredAt(): DateTimeImmutable;

    /** @return array<string, scalar|null> */
    public function payload(): array;
}
