<?php

declare(strict_types=1);

namespace Src\Application\Port;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;

/**
 * Баланс попыток участника. Списание должно быть атомарным на уровне хранилища
 * (Redis DECR с проверкой нижней границы), иначе параллельные запросы уводят баланс в минус.
 */
interface AttemptBalance
{
    public function grant(CampaignId $campaignId, ParticipantId $participantId, int $amount): void;

    /**
     * Атомарно списывает одну попытку. Возвращает false, если попыток не осталось.
     */
    public function consumeOne(CampaignId $campaignId, ParticipantId $participantId): bool;

    public function refundOne(CampaignId $campaignId, ParticipantId $participantId): void;

    public function remaining(CampaignId $campaignId, ParticipantId $participantId): int;

    public function reset(CampaignId $campaignId): void;
}
