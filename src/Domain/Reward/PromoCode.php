<?php

declare(strict_types=1);

namespace Src\Domain\Reward;

use DateTimeImmutable;

final readonly class PromoCode
{
    public function __construct(
        public string $code,
        public ?string $issuedToParticipantId,
        public ?DateTimeImmutable $issuedAt,
    ) {
    }
}
