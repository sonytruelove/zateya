<?php

declare(strict_types=1);

namespace Tests\Integration\Concerns;

use DateTimeImmutable;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Infrastructure\Persistence\Eloquent\EloquentCampaignRepository;

trait PersistsCampaign
{
    private int $campaignSeq = 0;

    protected function persistCampaign(): CampaignId
    {
        $this->campaignSeq++;
        $id = CampaignId::generate();
        (new EloquentCampaignRepository())->save(Campaign::createDraft(
            $id,
            Slug::fromString('integration-' . $this->campaignSeq),
            'Кампания для интеграционного теста',
            MechanicType::Wheel,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31')),
            CampaignTheme::default(),
            3,
        ));

        return $id;
    }
}
