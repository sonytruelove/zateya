<?php

declare(strict_types=1);

namespace Src\Domain\Event;

use DateTimeImmutable;

final readonly class AttemptPlayed implements DomainEvent
{
    public function __construct(
        public string $attemptId,
        public string $campaignId,
        public string $participantId,
        public bool $won,
        public int $score,
        public DateTimeImmutable $at,
    ) {
    }

    public function name(): string
    {
        return 'attempt.played';
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }

    public function payload(): array
    {
        return [
            'attempt_id' => $this->attemptId,
            'campaign_id' => $this->campaignId,
            'participant_id' => $this->participantId,
            'won' => $this->won,
            'score' => $this->score,
        ];
    }
}
