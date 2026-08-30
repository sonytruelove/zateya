<?php

declare(strict_types=1);

namespace Src\Domain\Reward;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;

interface RewardLedger
{
    /**
     * @return list<IssuedReward>
     */
    public function forParticipant(CampaignId $campaignId, ParticipantId $participantId): array;
}
