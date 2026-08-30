<?php

declare(strict_types=1);

namespace Src\Domain\Participation;

/**
 * Устойчивый признак участника внутри канала: идентификатор пользователя Telegram,
 * идентификатор VK или анонимный признак веб-браузера.
 */
final readonly class ChannelIdentity
{
    private function __construct(
        public Channel $channel,
        public string $externalId,
    ) {
    }

    public static function of(Channel $channel, string $externalId): self
    {
        $externalId = trim($externalId);
        if ($externalId === '' || strlen($externalId) > 128) {
            throw new InvalidChannelIdentity('Внешний идентификатор пуст или длиннее 128 символов.');
        }

        return new self($channel, $externalId);
    }

    public function equals(self $other): bool
    {
        return $this->channel === $other->channel && $this->externalId === $other->externalId;
    }

    public function fingerprint(): string
    {
        return "{$this->channel->value}:{$this->externalId}";
    }
}
