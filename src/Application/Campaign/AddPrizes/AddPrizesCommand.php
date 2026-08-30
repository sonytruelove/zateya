<?php

declare(strict_types=1);

namespace Src\Application\Campaign\AddPrizes;

final readonly class AddPrizesCommand
{
    public function __construct(
        public string $campaignId,
        public string $title,
        public int $quantity,
    ) {
    }
}
