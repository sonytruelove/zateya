<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\MechanicType;

/**
 * Хранимая конфигурация механики кампании в виде простого массива.
 * Разбор в конкретный объект механики выполняет MechanicFactory.
 */
final readonly class MechanicConfig
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public CampaignId $campaignId,
        public MechanicType $type,
        public array $settings,
    ) {
    }
}
