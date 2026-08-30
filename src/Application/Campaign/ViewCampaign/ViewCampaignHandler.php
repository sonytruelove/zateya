<?php

declare(strict_types=1);

namespace Src\Application\Campaign\ViewCampaign;

use Src\Application\Port\AttemptBalance;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\Slug;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Shared\Clock;

final readonly class ViewCampaignHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private AttemptBalance $balance,
        private Clock $clock,
    ) {
    }

    public function handle(ViewCampaignQuery $query): CampaignView
    {
        try {
            $campaign = $this->campaigns->bySlug(Slug::fromString($query->slug));
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        $attemptsLeft = 0;
        if ($query->participantId !== null) {
            $attemptsLeft = $this->balance->remaining($campaign->id, ParticipantId::fromString($query->participantId));
        }

        $period = $campaign->period();

        return new CampaignView(
            slug: (string) $campaign->slug,
            title: $campaign->title(),
            mechanic: $campaign->mechanic->value,
            mechanicTitle: $campaign->mechanic->title(),
            status: $campaign->status()->value,
            statusTitle: $campaign->status()->title(),
            startsAt: $period->startsAt->format(DATE_ATOM),
            endsAt: $period->endsAt->format(DATE_ATOM),
            colorHex: $campaign->theme()->colorHex,
            emoji: $campaign->theme()->emoji,
            acceptingAttempts: $campaign->isAcceptingAttempts($this->clock->now()),
            attemptsLeft: $attemptsLeft,
        );
    }
}
