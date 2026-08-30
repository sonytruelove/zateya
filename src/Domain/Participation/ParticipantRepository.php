<?php

declare(strict_types=1);

namespace Src\Domain\Participation;

use Src\Domain\Campaign\CampaignId;

interface ParticipantRepository
{
    public function save(Participant $participant): void;

    /** @throws ParticipantNotFound */
    public function byId(ParticipantId $id): Participant;

    public function byChannelIdentity(CampaignId $campaignId, ChannelIdentity $identity): ?Participant;

    public function countForCampaign(CampaignId $campaignId): int;

    public function deleteForCampaign(CampaignId $campaignId): void;
}
