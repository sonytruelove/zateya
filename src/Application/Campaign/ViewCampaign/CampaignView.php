<?php

declare(strict_types=1);

namespace Src\Application\Campaign\ViewCampaign;

final readonly class CampaignView
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $mechanic,
        public string $mechanicTitle,
        public string $status,
        public string $statusTitle,
        public string $startsAt,
        public string $endsAt,
        public string $colorHex,
        public string $emoji,
        public bool $acceptingAttempts,
        public int $attemptsLeft,
    ) {
    }
}
