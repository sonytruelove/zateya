<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\Attempt;
use Src\Domain\Participation\AttemptRepository;
use Src\Domain\Participation\ParticipantId;
use Src\Infrastructure\Persistence\Eloquent\Models\AttemptModel;

final class EloquentAttemptRepository implements AttemptRepository
{
    public function save(Attempt $attempt): void
    {
        AttemptModel::query()->create([
            'id' => $attempt->id,
            'campaign_id' => (string) $attempt->campaignId,
            'participant_id' => (string) $attempt->participantId,
            'won' => $attempt->outcome->won,
            'score' => $attempt->outcome->score,
            'detail' => $attempt->outcome->detail,
            'prize_id' => $attempt->prizeId,
            'promo_code' => $attempt->promoCode,
            'played_at' => $attempt->playedAt->format('Y-m-d H:i:sP'),
        ]);
    }

    public function countForParticipant(CampaignId $campaignId, ParticipantId $participantId): int
    {
        return AttemptModel::query()
            ->where('campaign_id', (string) $campaignId)
            ->where('participant_id', (string) $participantId)
            ->count();
    }

    public function countForCampaign(CampaignId $campaignId): int
    {
        return AttemptModel::query()->where('campaign_id', (string) $campaignId)->count();
    }

    public function countWinnersForCampaign(CampaignId $campaignId): int
    {
        return AttemptModel::query()
            ->where('campaign_id', (string) $campaignId)
            ->where('won', true)
            ->distinct()
            ->count('participant_id');
    }

    public function dailyActivity(CampaignId $campaignId, int $days): array
    {
        $since = (new DateTimeImmutable("-{$days} days"))->format('Y-m-d');

        $rows = AttemptModel::query()
            ->selectRaw('substr(played_at, 1, 10) as day, count(*) as total')
            ->where('campaign_id', (string) $campaignId)
            ->where('played_at', '>=', $since)
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = (new DateTimeImmutable("-{$offset} days"))->format('Y-m-d');
            $series[] = (int) ($rows[$day] ?? 0);
        }

        return $series;
    }

    public function deleteForCampaign(CampaignId $campaignId): void
    {
        AttemptModel::query()->where('campaign_id', (string) $campaignId)->delete();
    }
}
