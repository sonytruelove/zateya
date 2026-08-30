<?php

declare(strict_types=1);

namespace Src\Application\Participation\OpenSession;

/**
 * Открытие сессии участника в кампании через один из каналов.
 * $channelToken — подтверждённый идентификатор пользователя в канале
 * (id Telegram, id VK, анонимный признак браузера).
 */
final readonly class OpenSessionCommand
{
    public function __construct(
        public string $channel,
        public string $campaignSlug,
        public string $channelToken,
        public string $displayName,
    ) {
    }
}
