<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Reward\IssuedReward;
use Src\Domain\Reward\RewardLedger;

final class EloquentRewardLedger implements RewardLedger
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function forParticipant(CampaignId $campaignId, ParticipantId $participantId): array
    {
        $rows = $this->db->table('attempts')
            ->leftJoin('prizes', 'attempts.prize_id', '=', 'prizes.id')
            ->where('attempts.campaign_id', (string) $campaignId)
            ->where('attempts.participant_id', (string) $participantId)
            ->whereNotNull('attempts.prize_id')
            ->orderByDesc('attempts.played_at')
            ->get(['prizes.title as prize_title', 'attempts.promo_code', 'attempts.played_at']);

        return array_values($rows->map(static fn ($row): IssuedReward => new IssuedReward(
            (string) ($row->prize_title ?? 'Приз'),
            $row->promo_code !== null ? (string) $row->promo_code : null,
            new DateTimeImmutable((string) $row->played_at),
        ))->all());
    }
}
