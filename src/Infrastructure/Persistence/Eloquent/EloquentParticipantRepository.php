<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ChannelIdentity;
use Src\Domain\Participation\Participant;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Participation\ParticipantNotFound;
use Src\Domain\Participation\ParticipantRepository;
use Src\Infrastructure\Persistence\Eloquent\Models\ParticipantModel;

final class EloquentParticipantRepository implements ParticipantRepository
{
    public function save(Participant $participant): void
    {
        ParticipantModel::query()->updateOrCreate(
            ['id' => (string) $participant->id],
            [
                'campaign_id' => (string) $participant->campaignId,
                'channel' => $participant->identity->channel->value,
                'external_id' => $participant->identity->externalId,
                'display_name' => $participant->displayName(),
                'registered_at' => $participant->registeredAt->format('Y-m-d H:i:sP'),
            ],
        );
    }

    public function byId(ParticipantId $id): Participant
    {
        $row = ParticipantModel::query()->find((string) $id);
        if ($row === null) {
            throw ParticipantNotFound::withId($id);
        }

        return $this->hydrate($row);
    }

    public function byChannelIdentity(CampaignId $campaignId, ChannelIdentity $identity): ?Participant
    {
        $row = ParticipantModel::query()
            ->where('campaign_id', (string) $campaignId)
            ->where('channel', $identity->channel->value)
            ->where('external_id', $identity->externalId)
            ->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function countForCampaign(CampaignId $campaignId): int
    {
        return ParticipantModel::query()->where('campaign_id', (string) $campaignId)->count();
    }

    public function deleteForCampaign(CampaignId $campaignId): void
    {
        ParticipantModel::query()->where('campaign_id', (string) $campaignId)->delete();
    }

    private function hydrate(ParticipantModel $row): Participant
    {
        return Participant::fromState([
            'id' => $row->id,
            'campaign_id' => $row->campaign_id,
            'channel' => $row->channel,
            'external_id' => $row->external_id,
            'display_name' => $row->display_name,
            'registered_at' => $row->registered_at,
        ]);
    }
}
