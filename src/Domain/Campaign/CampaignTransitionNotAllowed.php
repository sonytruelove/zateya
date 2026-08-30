<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

use Src\Domain\Shared\DomainException;

final class CampaignTransitionNotAllowed extends DomainException
{
    public static function between(CampaignStatus $from, CampaignStatus $to): self
    {
        return new self("Переход кампании из состояния «{$from->title()}» в «{$to->title()}» не разрешён.");
    }
}
