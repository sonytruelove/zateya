<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\Attempt;
use Src\Domain\Participation\AttemptRepository;
use Src\Domain\Participation\ParticipantId;

final class InMemoryAttemptRepository implements AttemptRepository
{
    /** @var list<Attempt> */
    private array $attempts = [];

    public function save(Attempt $attempt): void
    {
        $this->attempts[] = $attempt;
    }

    public function countForParticipant(CampaignId $campaignId, ParticipantId $participantId): int
    {
        return count($this->filter($campaignId, $participantId));
    }

    public function countForCampaign(CampaignId $campaignId): int
    {
        return count($this->filter($campaignId, null));
    }

    public function countWinnersForCampaign(CampaignId $campaignId): int
    {
        $winners = [];
        foreach ($this->filter($campaignId, null) as $attempt) {
            if ($attempt->outcome->won) {
                $winners[(string) $attempt->participantId] = true;
            }
        }

        return count($winners);
    }

    public function dailyActivity(CampaignId $campaignId, int $days): array
    {
        return array_fill(0, $days, 0);
    }

    public function deleteForCampaign(CampaignId $campaignId): void
    {
        $this->attempts = array_values(array_filter(
            $this->attempts,
            static fn (Attempt $a): bool => !$a->campaignId->equals($campaignId),
        ));
    }

    /**
     * @return list<Attempt>
     */
    private function filter(CampaignId $campaignId, ?ParticipantId $participantId): array
    {
        return array_values(array_filter($this->attempts, static function (Attempt $a) use ($campaignId, $participantId): bool {
            if (!$a->campaignId->equals($campaignId)) {
                return false;
            }

            return $participantId === null || $a->participantId->equals($participantId);
        }));
    }
}
