<?php

declare(strict_types=1);

namespace Src\Application\Campaign\PublishCampaign;

use Src\Application\Port\EventPublisher;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\CampaignTransitionNotAllowed;
use Src\Domain\Shared\Clock;

final readonly class PublishCampaignHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private EventPublisher $events,
        private Clock $clock,
    ) {
    }

    public function handle(PublishCampaignCommand $command): void
    {
        try {
            $campaign = $this->campaigns->byId(CampaignId::fromString($command->campaignId));
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        try {
            $campaign->publish($this->clock->now());
        } catch (CampaignTransitionNotAllowed $e) {
            throw UseCaseException::conflict('publish_not_allowed', $e->getMessage());
        }

        $this->campaigns->save($campaign);
        $this->events->publish(...$campaign->releaseEvents());
    }
}
