<?php

declare(strict_types=1);

namespace Src\Infrastructure\Balance;

use Src\Application\Port\AttemptBalance;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;

/**
 * Баланс попыток в памяти процесса. Для тестов и локального запуска без Redis.
 */
final class ArrayAttemptBalance implements AttemptBalance
{
    /** @var array<string, int> */
    private array $counters = [];

    public function grant(CampaignId $campaignId, ParticipantId $participantId, int $amount): void
    {
        $this->counters[$this->key($campaignId, $participantId)] = $this->value($campaignId, $participantId) + $amount;
    }

    public function consumeOne(CampaignId $campaignId, ParticipantId $participantId): bool
    {
        $key = $this->key($campaignId, $participantId);
        if ($this->value($campaignId, $participantId) <= 0) {
            return false;
        }

        $this->counters[$key]--;

        return true;
    }

    public function refundOne(CampaignId $campaignId, ParticipantId $participantId): void
    {
        $this->counters[$this->key($campaignId, $participantId)] = $this->value($campaignId, $participantId) + 1;
    }

    public function remaining(CampaignId $campaignId, ParticipantId $participantId): int
    {
        return max(0, $this->value($campaignId, $participantId));
    }

    public function reset(CampaignId $campaignId): void
    {
        $prefix = "{$campaignId}:";
        foreach (array_keys($this->counters) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->counters[$key]);
            }
        }
    }

    private function value(CampaignId $campaignId, ParticipantId $participantId): int
    {
        return $this->counters[$this->key($campaignId, $participantId)] ?? 0;
    }

    private function key(CampaignId $campaignId, ParticipantId $participantId): string
    {
        return "{$campaignId}:{$participantId}";
    }
}
