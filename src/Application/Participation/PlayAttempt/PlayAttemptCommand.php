<?php

declare(strict_types=1);

namespace Src\Application\Participation\PlayAttempt;

final readonly class PlayAttemptCommand
{
    /**
     * @param array<string, mixed> $payload данные хода, специфичные для механики
     */
    public function __construct(
        public string $campaignSlug,
        public string $participantId,
        public array $payload,
    ) {
    }
}
