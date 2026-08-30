<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic;

use Src\Domain\Campaign\MechanicType;
use Src\Domain\Mechanic\Quiz\QuizMechanic;
use Src\Domain\Mechanic\Wheel\WheelMechanic;

/**
 * Собирает объект механики из хранимой конфигурации.
 */
final class MechanicFactory
{
    public function fromConfig(MechanicConfig $config): Mechanic
    {
        return match ($config->type) {
            MechanicType::Quiz => QuizMechanic::fromSettings($config->settings),
            MechanicType::Wheel => WheelMechanic::fromSettings($config->settings),
            MechanicType::Collection, MechanicType::Promo => throw new InvalidMechanicInput(
                "Механика «{$config->type->title()}» ещё не реализована.",
            ),
        };
    }
}
