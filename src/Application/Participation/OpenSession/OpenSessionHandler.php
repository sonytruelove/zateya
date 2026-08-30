<?php

declare(strict_types=1);

namespace Src\Application\Participation\OpenSession;

use Src\Application\Port\AttemptBalance;
use Src\Application\Port\ParticipantSessions;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\Slug;
use Src\Domain\Participation\Channel;
use Src\Domain\Participation\ChannelIdentity;
use Src\Domain\Participation\Participant;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Participation\ParticipantRepository;
use Src\Domain\Shared\Clock;
use Src\Domain\Shared\DomainException;

final readonly class OpenSessionHandler
{
    private const SESSION_TTL = 21_600;

    public function __construct(
        private CampaignRepository $campaigns,
        private ParticipantRepository $participants,
        private AttemptBalance $balance,
        private ParticipantSessions $sessions,
        private Clock $clock,
    ) {
    }

    public function handle(OpenSessionCommand $command): OpenSessionResult
    {
        $campaign = $this->loadCampaign($command->campaignSlug);
        $identity = $this->buildIdentity($command);

        $existing = $this->participants->byChannelIdentity($campaign->id, $identity);
        $isNew = $existing === null;
        $participant = $existing ?? $this->registerNew($campaign, $identity, $command->displayName);

        if ($isNew) {
            $this->balance->grant($campaign->id, $participant->id, $campaign->attemptsPerParticipant());
        }

        return new OpenSessionResult(
            participantId: (string) $participant->id,
            displayName: $participant->displayName(),
            attemptsLeft: $this->balance->remaining($campaign->id, $participant->id),
            token: $this->sessions->issue($participant->id, self::SESSION_TTL),
            isNew: $isNew,
        );
    }

    private function loadCampaign(string $slug): Campaign
    {
        try {
            return $this->campaigns->bySlug(Slug::fromString($slug));
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }
    }

    private function buildIdentity(OpenSessionCommand $command): ChannelIdentity
    {
        try {
            return ChannelIdentity::of(Channel::fromExternal($command->channel), $command->channelToken);
        } catch (DomainException $e) {
            throw UseCaseException::unprocessable('invalid_channel_identity', $e->getMessage());
        }
    }

    private function registerNew(Campaign $campaign, ChannelIdentity $identity, string $displayName): Participant
    {
        $participant = Participant::register(
            ParticipantId::generate(),
            $campaign->id,
            $identity,
            $displayName,
            $this->clock->now(),
        );
        $this->participants->save($participant);

        return $participant;
    }
}
