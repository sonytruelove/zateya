<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Application\Participation\PlayAttempt\PlayResult;
use Src\Presentation\Channel\Telegram\TelegramMessageComposer;

final class TelegramMessageComposerTest extends TestCase
{
    #[Test]
    public function a_winning_result_lists_the_prize_promo_code_and_remaining_attempts(): void
    {
        $text = (new TelegramMessageComposer())->forResult(
            new PlayResult(true, 40, 'Выпал сектор «Приз».', 2, 'Подарок', 'PROMO-7'),
        );

        self::assertStringContainsString('Есть победа!', $text);
        self::assertStringContainsString('Приз: Подарок', $text);
        self::assertStringContainsString('Промокод: PROMO-7', $text);
        self::assertStringContainsString('Осталось попыток: 2', $text);
    }

    #[Test]
    public function the_message_never_contains_markup_that_a_prize_title_could_inject(): void
    {
        $text = (new TelegramMessageComposer())->forResult(
            new PlayResult(true, 10, 'ok', 1, '<b>жирный</b> & <script>', null),
        );

        // Текст отправляется без parse_mode: угловые скобки из названия приза
        // попадают в сообщение буквально и не превращаются в разметку.
        self::assertStringContainsString('<b>жирный</b> & <script>', $text);
    }
}
