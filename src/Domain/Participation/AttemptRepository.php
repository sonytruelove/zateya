<?php

declare(strict_types=1);

namespace Src\Domain\Participation;

use Src\Domain\Campaign\CampaignId;

interface AttemptRepository
{
    public function save(Attempt $attempt): void;

    public function countForParticipant(CampaignId $campaignId, ParticipantId $participantId): int;

    public function countForCampaign(CampaignId $campaignId): int;

    public function countWinnersForCampaign(CampaignId $campaignId): int;

    /**
     * @return list<int> суточная активность за последние $days дней, старые дни первыми
     */
    public function dailyActivity(CampaignId $campaignId, int $days): array;

    public function deleteForCampaign(CampaignId $campaignId): void;
}
