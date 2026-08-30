<?php

declare(strict_types=1);

namespace Src\Domain\Reward;

/**
 * Позиция призового фонда кампании: название и остаток.
 */
final readonly class Prize
{
    public function __construct(
        public PrizeId $id,
        public string $title,
        public int $remaining,
    ) {
    }
}
