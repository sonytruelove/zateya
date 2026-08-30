<?php

declare(strict_types=1);

namespace Src\Presentation\Channel\Telegram;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Participation\OpenSession\OpenSessionCommand;
use Src\Application\Participation\OpenSession\OpenSessionHandler;
use Src\Application\Participation\PlayAttempt\PlayAttemptCommand;
use Src\Application\Participation\PlayAttempt\PlayAttemptHandler;
use Src\Application\Shared\UseCaseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Приём обновлений Telegram-бота. Подлинность запроса подтверждается секретом
 * в адресе вебхука и заголовком «X-Telegram-Bot-Api-Secret-Token».
 */
final class TelegramWebhookController
{
    public function __construct(
        private readonly OpenSessionHandler $openSession,
        private readonly PlayAttemptHandler $playAttempt,
        private readonly TelegramMessageComposer $composer,
        private readonly string $webhookSecret,
        private readonly string $defaultCampaignSlug,
    ) {
    }

    public function handle(Request $request, string $secret): JsonResponse
    {
        if (!$this->secretMatches($secret, $request)) {
            return response()->json(['error' => ['code' => 'unauthorized', 'message' => 'Неверный секрет вебхука.']], Response::HTTP_UNAUTHORIZED);
        }

        $message = $request->input('message');
        if (!is_array($message) || !isset($message['from']['id'])) {
            return response()->json(['ok' => true]);
        }

        $chatId = (string) $message['from']['id'];
        $name = trim((string) ($message['from']['first_name'] ?? 'Игрок'));
        $text = trim((string) ($message['text'] ?? ''));

        return response()->json(['ok' => true, 'reply' => $this->reply($chatId, $name, $text)]);
    }

    private function reply(string $chatId, string $name, string $text): string
    {
        if (!str_starts_with($text, '/play')) {
            return $this->composer->forHelp();
        }

        try {
            $session = $this->openSession->handle(new OpenSessionCommand('telegram', $this->defaultCampaignSlug, $chatId, $name));
            $result = $this->playAttempt->handle(new PlayAttemptCommand($this->defaultCampaignSlug, $session->participantId, []));

            return $this->composer->forResult($result);
        } catch (UseCaseException $e) {
            return $e->getMessage();
        }
    }

    private function secretMatches(string $fromUrl, Request $request): bool
    {
        $header = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        return $this->webhookSecret !== ''
            && hash_equals($this->webhookSecret, $fromUrl)
            && hash_equals($this->webhookSecret, $header);
    }
}
