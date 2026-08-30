<?php

declare(strict_types=1);

namespace Src\Domain\Participation;

use DateTimeImmutable;
use Src\Domain\Campaign\CampaignId;

/**
 * Участник конкретной кампании, привязанный к каналу входа.
 */
final class Participant
{
    private function __construct(
        public readonly ParticipantId $id,
        public readonly CampaignId $campaignId,
        public readonly ChannelIdentity $identity,
        private string $displayName,
        public readonly DateTimeImmutable $registeredAt,
    ) {
    }

    public static function register(
        ParticipantId $id,
        CampaignId $campaignId,
        ChannelIdentity $identity,
        string $displayName,
        DateTimeImmutable $registeredAt,
    ): self {
        return new self($id, $campaignId, $identity, self::normalizeName($displayName), $registeredAt);
    }

    /**
     * @param array{id:string,campaign_id:string,channel:string,external_id:string,display_name:string,registered_at:string} $row
     */
    public static function fromState(array $row): self
    {
        return new self(
            ParticipantId::fromString($row['id']),
            CampaignId::fromString($row['campaign_id']),
            ChannelIdentity::of(Channel::from($row['channel']), $row['external_id']),
            $row['display_name'],
            new DateTimeImmutable($row['registered_at']),
        );
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    private static function normalizeName(string $displayName): string
    {
        $displayName = trim($displayName);
        if ($displayName === '') {
            return 'Участник';
        }

        return mb_substr($displayName, 0, 60);
    }
}
