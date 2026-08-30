<?php

declare(strict_types=1);

namespace Src\Application\Campaign\CampaignStats;

use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Participation\AttemptRepository;
use Src\Domain\Participation\ParticipantRepository;
use Src\Domain\Reward\PrizePool;
use Src\Domain\Reward\PromoCodeBook;

final readonly class CampaignStatsHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private AttemptRepository $attempts,
        private ParticipantRepository $participants,
        private PrizePool $prizePool,
        private PromoCodeBook $promoCodes,
    ) {
    }

    public function handle(CampaignStatsQuery $query): CampaignStats
    {
        $id = CampaignId::fromString($query->campaignId);
        try {
            $this->campaigns->byId($id);
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        $days = max(1, min(90, $query->activityDays));

        return new CampaignStats(
            attempts: $this->attempts->countForCampaign($id),
            participants: $this->participants->countForCampaign($id),
            winners: $this->attempts->countWinnersForCampaign($id),
            prizePoolLeft: $this->prizePool->remaining($id),
            promoCodesLeft: $this->promoCodes->remaining($id),
            activity: $this->attempts->dailyActivity($id, $days),
        );
    }
}
