<?php

declare(strict_types=1);

namespace Src\Application\Campaign\UploadPromoCodes;

final readonly class UploadPromoCodesCommand
{
    /**
     * @param list<string> $codes
     */
    public function __construct(
        public string $campaignId,
        public array $codes,
    ) {
    }
}
