<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

use DateTimeImmutable;

/**
 * Период проведения кампании. Инвариант: начало строго раньше конца.
 */
final readonly class CampaignPeriod
{
    private function __construct(
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt,
    ) {
    }

    public static function between(DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): self
    {
        if ($startsAt >= $endsAt) {
            throw new InvalidCampaignPeriod(
                "Начало {$startsAt->format(DATE_ATOM)} должно быть раньше конца {$endsAt->format(DATE_ATOM)}.",
            );
        }

        return new self($startsAt, $endsAt);
    }

    public function contains(DateTimeImmutable $moment): bool
    {
        return $moment >= $this->startsAt && $moment < $this->endsAt;
    }

    public function isOver(DateTimeImmutable $moment): bool
    {
        return $moment >= $this->endsAt;
    }

    public function startsInFuture(DateTimeImmutable $moment): bool
    {
        return $moment < $this->startsAt;
    }
}
