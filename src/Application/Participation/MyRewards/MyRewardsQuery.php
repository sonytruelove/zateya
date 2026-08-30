<?php

declare(strict_types=1);

namespace Src\Application\Participation\MyRewards;

final readonly class MyRewardsQuery
{
    public function __construct(
        public string $campaignSlug,
        public string $participantId,
    ) {
    }
}
