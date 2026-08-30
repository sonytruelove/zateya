<?php

declare(strict_types=1);

namespace Src\Domain\Reward;

use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;

/**
 * Пул промокодов кампании. Выдача следующего кода должна быть атомарной:
 * один код не может достаться двум участникам.
 */
interface PromoCodeBook
{
    /**
     * @param list<string> $codes
     * @return int сколько новых кодов добавлено (дубликаты отбрасываются)
     */
    public function add(CampaignId $campaignId, array $codes): int;

    /**
     * Атомарно помечает следующий свободный код выданным участнику и возвращает его,
     * либо null, если свободных кодов не осталось.
     */
    public function issueNext(CampaignId $campaignId, ParticipantId $participantId): ?PromoCode;

    public function remaining(CampaignId $campaignId): int;

    public function total(CampaignId $campaignId): int;

    public function clearForCampaign(CampaignId $campaignId): void;
}
