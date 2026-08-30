<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

/**
 * Состояние жизненного цикла кампании. Разрешённые переходы описаны в Campaign.
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Paused = 'paused';
    case Finished = 'finished';
    case Archived = 'archived';

    public function title(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Scheduled => 'Запланирована',
            self::Active => 'Активна',
            self::Paused => 'Приостановлена',
            self::Finished => 'Завершена',
            self::Archived => 'В архиве',
        };
    }
}
