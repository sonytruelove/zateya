<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

use Src\Domain\Shared\DomainException;

final class CampaignNotFound extends DomainException
{
    public static function withId(CampaignId $id): self
    {
        return new self("Кампания с идентификатором {$id} не найдена.");
    }

    public static function withSlug(Slug $slug): self
    {
        return new self("Кампания с адресом «{$slug}» не найдена.");
    }
}
