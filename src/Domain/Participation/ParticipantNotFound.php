<?php

declare(strict_types=1);

namespace Src\Domain\Participation;

use Src\Domain\Shared\DomainException;

final class ParticipantNotFound extends DomainException
{
    public static function withId(ParticipantId $id): self
    {
        return new self("Участник с идентификатором {$id} не найден.");
    }
}
