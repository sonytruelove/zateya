<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ChannelIdentity;
use Src\Domain\Participation\Participant;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Participation\ParticipantNotFound;
use Src\Domain\Participation\ParticipantRepository;

final class InMemoryParticipantRepository implements ParticipantRepository
{
    /** @var array<string, Participant> */
    private array $byId = [];

    public function save(Participant $participant): void
    {
        $this->byId[(string) $participant->id] = $participant;
    }

    public function byId(ParticipantId $id): Participant
    {
        return $this->byId[(string) $id] ?? throw ParticipantNotFound::withId($id);
    }

    public function byChannelIdentity(CampaignId $campaignId, ChannelIdentity $identity): ?Participant
    {
        foreach ($this->byId as $participant) {
            if ($participant->campaignId->equals($campaignId) && $participant->identity->equals($identity)) {
                return $participant;
            }
        }

        return null;
    }

    public function countForCampaign(CampaignId $campaignId): int
    {
        return count(array_filter(
            $this->byId,
            static fn (Participant $p): bool => $p->campaignId->equals($campaignId),
        ));
    }

    public function deleteForCampaign(CampaignId $campaignId): void
    {
        foreach ($this->byId as $key => $participant) {
            if ($participant->campaignId->equals($campaignId)) {
                unset($this->byId[$key]);
            }
        }
    }
}
