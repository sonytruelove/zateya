<?php

declare(strict_types=1);

namespace Src\Infrastructure\Balance;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Src\Application\Port\AttemptBalance;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;

/**
 * Баланс попыток на счётчиках Redis. Списание атомарно: сценарий DECR с откатом,
 * если значение ушло ниже нуля, — параллельные запросы не могут списать лишнего.
 */
final class RedisAttemptBalance implements AttemptBalance
{
    public function __construct(
        private readonly RedisFactory $redis,
        private readonly string $connection = 'default',
    ) {
    }

    public function grant(CampaignId $campaignId, ParticipantId $participantId, int $amount): void
    {
        $this->client()->incrby($this->key($campaignId, $participantId), $amount);
    }

    public function consumeOne(CampaignId $campaignId, ParticipantId $participantId): bool
    {
        $key = $this->key($campaignId, $participantId);
        $left = (int) $this->client()->decr($key);
        if ($left < 0) {
            $this->client()->incr($key);

            return false;
        }

        return true;
    }

    public function refundOne(CampaignId $campaignId, ParticipantId $participantId): void
    {
        $this->client()->incr($this->key($campaignId, $participantId));
    }

    public function remaining(CampaignId $campaignId, ParticipantId $participantId): int
    {
        return max(0, (int) $this->client()->get($this->key($campaignId, $participantId)));
    }

    public function reset(CampaignId $campaignId): void
    {
        $client = $this->client();
        $keys = $client->keys("zateya:balance:{$campaignId}:*");
        if ($keys !== []) {
            $client->del($keys);
        }
    }

    private function client(): mixed
    {
        return $this->redis->connection($this->connection);
    }

    private function key(CampaignId $campaignId, ParticipantId $participantId): string
    {
        return "zateya:balance:{$campaignId}:{$participantId}";
    }
}
