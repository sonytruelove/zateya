<?php

declare(strict_types=1);

namespace Src\Domain\Participation;

/**
 * Канал, через который участник пришёл в кампанию.
 */
enum Channel: string
{
    case Web = 'web';
    case Telegram = 'telegram';
    case Vk = 'vk';

    public static function fromExternal(string $raw): self
    {
        return self::tryFrom(strtolower(trim($raw)))
            ?? throw new UnknownChannel("Канал «{$raw}» неизвестен; допустимы web, telegram, vk.");
    }

    public function title(): string
    {
        return match ($this) {
            self::Web => 'Веб',
            self::Telegram => 'Telegram',
            self::Vk => 'VK',
        };
    }
}
