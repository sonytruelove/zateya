<?php

declare(strict_types=1);

namespace Src\Infrastructure\Realtime;

use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;
use Src\Application\Port\RealtimePublisher;
use Src\Domain\Campaign\Slug;
use Src\Domain\Leaderboard\LeaderboardEntry;
use Src\Domain\Participation\ParticipantId;
use Throwable;

/**
 * Публикатор в Centrifugo через его серверный интерфейс HTTP (`/api`).
 * Ошибка доставки логируется, но не срывает розыгрыш попытки.
 */
final class CentrifugoPublisher implements RealtimePublisher
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly LoggerInterface $logger,
        private readonly string $apiUrl,
        private readonly string $apiKey,
    ) {
    }

    public function pushLeaderboard(Slug $slug, array $entries): void
    {
        $this->broadcast("campaign:{$slug}:leaderboard", [
            'type' => 'leaderboard',
            'entries' => array_map(
                static fn (LeaderboardEntry $e): array => [
                    'rank' => $e->rank,
                    'display_name' => $e->displayName,
                    'score' => $e->score,
                ],
                $entries,
            ),
        ]);
    }

    public function pushToParticipant(ParticipantId $participantId, string $type, array $data): void
    {
        $this->broadcast("participant:{$participantId}", ['type' => $type] + $data);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function broadcast(string $channel, array $payload): void
    {
        try {
            $response = $this->http
                ->withHeaders(['Authorization' => "apikey {$this->apiKey}"])
                ->acceptJson()
                ->post($this->apiUrl, ['method' => 'publish', 'params' => ['channel' => $channel, 'data' => $payload]]);

            if ($response->failed()) {
                $this->logger->warning('Centrifugo вернул ошибку публикации.', [
                    'channel' => $channel,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Не удалось опубликовать сообщение в Centrifugo.', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
