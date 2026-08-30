<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

/**
 * Оформление витрины кампании: акцентный цвет и эмодзи-метафора.
 */
final readonly class CampaignTheme
{
    private const HEX = '/^#[0-9a-f]{6}$/i';

    private function __construct(
        public string $colorHex,
        public string $emoji,
    ) {
    }

    public static function of(string $colorHex, string $emoji): self
    {
        if (preg_match(self::HEX, $colorHex) !== 1) {
            throw new InvalidCampaignTheme("Цвет «{$colorHex}» должен быть в форме #RRGGBB.");
        }

        $emoji = trim($emoji);
        if ($emoji === '' || mb_strlen($emoji) > 8) {
            throw new InvalidCampaignTheme('Эмодзи-метафора не задана или слишком длинная.');
        }

        return new self(strtolower($colorHex), $emoji);
    }

    public static function default(): self
    {
        return new self('#0b57d0', '🎯');
    }
}
