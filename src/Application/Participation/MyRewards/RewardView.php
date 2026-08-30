<?php

declare(strict_types=1);

namespace Src\Application\Participation\MyRewards;

final readonly class RewardView
{
    public function __construct(
        public string $title,
        public ?string $promoCode,
        public string $awardedAt,
    ) {
    }
}
