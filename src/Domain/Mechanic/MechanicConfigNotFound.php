<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic;

use Src\Domain\Shared\DomainException;

final class MechanicConfigNotFound extends DomainException
{
    public static function forCampaign(string $campaignId): self
    {
        return new self("Конфигурация механики для кампании {$campaignId} не найдена.");
    }
}
