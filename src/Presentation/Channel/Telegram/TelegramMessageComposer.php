<?php

declare(strict_types=1);

namespace Src\Presentation\Channel\Telegram;

use Src\Application\Participation\PlayAttempt\PlayResult;

/**
 * Собирает текст ответа Telegram-бота. Всегда обычный текст без разметки HTML/Markdown,
 * поэтому название кампании и приза не могут внедрить форматирование или разметку.
 */
final class TelegramMessageComposer
{
    public function forResult(PlayResult $result): string
    {
        $lines = [$result->won ? 'Есть победа!' : 'В этот раз не повезло.', $result->detail];

        if ($result->prizeTitle !== null) {
            $lines[] = "Приз: {$result->prizeTitle}";
        }
        if ($result->promoCode !== null) {
            $lines[] = "Промокод: {$result->promoCode}";
        }
        $lines[] = "Осталось попыток: {$result->attemptsLeft}";

        return implode("\n", $lines);
    }

    public function forHelp(): string
    {
        return implode("\n", [
            'Отправьте /play, чтобы разыграть попытку в текущей кампании.',
            'Команда /score покажет вашу позицию в рейтинге.',
        ]);
    }
}
