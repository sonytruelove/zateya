<?php

declare(strict_types=1);

namespace Src\Domain\Reward;

use DateTimeImmutable;

/**
 * Выданная участнику награда для раздела «Мои призы».
 */
final readonly class IssuedReward
{
    public function __construct(
        public string $title,
        public ?string $promoCode,
        public DateTimeImmutable $awardedAt,
    ) {
    }
}
