<?php

declare(strict_types=1);

namespace Src\Application\Participation\MyRewards;

use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\Slug;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Reward\RewardLedger;

final readonly class MyRewardsHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private RewardLedger $ledger,
    ) {
    }

    /**
     * @return list<RewardView>
     */
    public function handle(MyRewardsQuery $query): array
    {
        try {
            $campaign = $this->campaigns->bySlug(Slug::fromString($query->campaignSlug));
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        $rewards = $this->ledger->forParticipant($campaign->id, ParticipantId::fromString($query->participantId));

        return array_map(
            static fn ($reward): RewardView => new RewardView(
                $reward->title,
                $reward->promoCode,
                $reward->awardedAt->format(DATE_ATOM),
            ),
            $rewards,
        );
    }
}
