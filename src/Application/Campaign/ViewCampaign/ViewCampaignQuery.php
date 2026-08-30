<?php

declare(strict_types=1);

namespace Src\Application\Campaign\ViewCampaign;

final readonly class ViewCampaignQuery
{
    public function __construct(
        public string $slug,
        public ?string $participantId = null,
    ) {
    }
}
