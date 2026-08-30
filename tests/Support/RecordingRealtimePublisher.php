<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Application\Port\RealtimePublisher;
use Src\Domain\Campaign\Slug;
use Src\Domain\Participation\ParticipantId;

final class RecordingRealtimePublisher implements RealtimePublisher
{
    /** @var list<array{channel: string, payload: mixed}> */
    public array $messages = [];

    public function pushLeaderboard(Slug $slug, array $entries): void
    {
        $this->messages[] = ['channel' => "campaign:{$slug}:leaderboard", 'payload' => $entries];
    }

    public function pushToParticipant(ParticipantId $participantId, string $type, array $data): void
    {
        $this->messages[] = ['channel' => "participant:{$participantId}", 'payload' => ['type' => $type] + $data];
    }

    public function typesForParticipant(): array
    {
        $types = [];
        foreach ($this->messages as $message) {
            if (str_starts_with($message['channel'], 'participant:') && is_array($message['payload'])) {
                $types[] = $message['payload']['type'] ?? null;
            }
        }

        return $types;
    }
}
