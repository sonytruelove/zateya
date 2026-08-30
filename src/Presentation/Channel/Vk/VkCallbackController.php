<?php

declare(strict_types=1);

namespace Src\Presentation\Channel\Vk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Participation\OpenSession\OpenSessionCommand;
use Src\Application\Participation\OpenSession\OpenSessionHandler;
use Src\Application\Participation\PlayAttempt\PlayAttemptCommand;
use Src\Application\Participation\PlayAttempt\PlayAttemptHandler;
use Src\Application\Shared\UseCaseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Обработчик обратных вызовов VK. Тип «confirmation» отвечает подтверждающим кодом,
 * тип «attempt» — разыгрывает попытку от имени пользователя VK после проверки секрета.
 */
final class VkCallbackController
{
    public function __construct(
        private readonly OpenSessionHandler $openSession,
        private readonly PlayAttemptHandler $playAttempt,
        private readonly string $confirmationToken,
        private readonly string $secretKey,
        private readonly string $defaultCampaignSlug,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $type = (string) $request->input('type');

        if ($type === 'confirmation') {
            return response()->json(['confirmation' => $this->confirmationToken]);
        }

        if ($this->secretKey === '' || !hash_equals($this->secretKey, (string) $request->input('secret'))) {
            return response()->json(['error' => ['code' => 'unauthorized', 'message' => 'Неверный секрет обратного вызова.']], Response::HTTP_UNAUTHORIZED);
        }

        if ($type !== 'attempt') {
            return response()->json(['response' => 'ok']);
        }

        return response()->json(['response' => $this->play($request)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function play(Request $request): array
    {
        $userId = (string) $request->input('object.user_id', $request->input('user_id', ''));
        if ($userId === '') {
            return ['status' => 'ignored'];
        }

        try {
            $session = $this->openSession->handle(new OpenSessionCommand('vk', $this->defaultCampaignSlug, $userId, 'Пользователь VK'));
            $result = $this->playAttempt->handle(new PlayAttemptCommand($this->defaultCampaignSlug, $session->participantId, []));

            return ['won' => $result->won, 'score' => $result->score, 'promo_code' => $result->promoCode, 'attempts_left' => $result->attemptsLeft];
        } catch (UseCaseException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
