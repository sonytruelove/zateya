<?php

declare(strict_types=1);

namespace Src\Application\Leaderboard\ViewLeaderboard;

use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\Slug;
use Src\Domain\Leaderboard\Leaderboard;
use Src\Domain\Leaderboard\LeaderboardStore;
use Src\Domain\Participation\ParticipantId;

final readonly class ViewLeaderboardHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private LeaderboardStore $store,
    ) {
    }

    public function handle(ViewLeaderboardQuery $query): Leaderboard
    {
        try {
            $campaign = $this->campaigns->bySlug(Slug::fromString($query->slug));
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        $limit = max(1, min(100, $query->limit));
        $me = $query->participantId !== null
            ? $this->store->positionOf($campaign->id, ParticipantId::fromString($query->participantId))
            : null;

        return new Leaderboard($this->store->top($campaign->id, $limit), $me);
    }
}
