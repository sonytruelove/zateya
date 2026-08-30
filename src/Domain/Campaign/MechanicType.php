<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

/**
 * Тип игровой механики кампании.
 */
enum MechanicType: string
{
    case Quiz = 'quiz';
    case Wheel = 'wheel';
    case Collection = 'collection';
    case Promo = 'promo';

    public function isImplemented(): bool
    {
        return match ($this) {
            self::Quiz, self::Wheel => true,
            self::Collection, self::Promo => false,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Quiz => 'Викторина',
            self::Wheel => 'Колесо фортуны',
            self::Collection => 'Собери набор',
            self::Promo => 'Промокоды',
        };
    }
}
