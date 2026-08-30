<?php

declare(strict_types=1);

namespace Src\Application\Campaign\CreateCampaign;

/**
 * Данные для создания кампании-черновика вместе с настройками её механики.
 */
final readonly class CreateCampaignCommand
{
    /**
     * @param array<string, mixed> $mechanicSettings
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $mechanic,
        public string $startsAt,
        public string $endsAt,
        public string $colorHex,
        public string $emoji,
        public int $attemptsPerParticipant,
        public array $mechanicSettings,
    ) {
    }
}
