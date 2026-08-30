<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Reward\IssuedReward;
use Src\Domain\Reward\RewardLedger;

final class InMemoryRewardLedger implements RewardLedger
{
    /** @var list<array{campaign: string, participant: string, reward: IssuedReward}> */
    private array $rows = [];

    public function record(CampaignId $campaignId, ParticipantId $participantId, string $title, ?string $promoCode): void
    {
        $this->rows[] = [
            'campaign' => (string) $campaignId,
            'participant' => (string) $participantId,
            'reward' => new IssuedReward($title, $promoCode, new DateTimeImmutable()),
        ];
    }

    public function forParticipant(CampaignId $campaignId, ParticipantId $participantId): array
    {
        $rewards = [];
        foreach ($this->rows as $row) {
            if ($row['campaign'] === (string) $campaignId && $row['participant'] === (string) $participantId) {
                $rewards[] = $row['reward'];
            }
        }

        return $rewards;
    }
}
