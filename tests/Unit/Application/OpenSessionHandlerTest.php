<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Application\Participation\OpenSession\OpenSessionCommand;
use Src\Application\Participation\OpenSession\OpenSessionHandler;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Infrastructure\Balance\ArrayAttemptBalance;
use Src\Infrastructure\Session\ArrayParticipantSessions;
use Tests\Support\FixedClock;
use Tests\Support\InMemoryCampaignRepository;
use Tests\Support\InMemoryParticipantRepository;

final class OpenSessionHandlerTest extends TestCase
{
    private InMemoryCampaignRepository $campaigns;
    private InMemoryParticipantRepository $participants;
    private ArrayAttemptBalance $balance;
    private ArrayParticipantSessions $sessions;
    private OpenSessionHandler $handler;

    protected function setUp(): void
    {
        $this->campaigns = new InMemoryCampaignRepository();
        $this->participants = new InMemoryParticipantRepository();
        $this->balance = new ArrayAttemptBalance();
        $this->sessions = new ArrayParticipantSessions();
        $this->handler = new OpenSessionHandler(
            $this->campaigns,
            $this->participants,
            $this->balance,
            $this->sessions,
            FixedClock::at('2026-02-01'),
        );

        $campaign = Campaign::createDraft(
            CampaignId::generate(),
            Slug::fromString('open-me'),
            'Кампания для входа',
            MechanicType::Wheel,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31')),
            CampaignTheme::default(),
            4,
        );
        $campaign->publish(new DateTimeImmutable('2026-02-01'));
        $this->campaigns->save($campaign);
    }

    #[Test]
    public function a_first_visit_registers_a_participant_and_grants_the_campaign_quota(): void
    {
        $result = $this->handler->handle(new OpenSessionCommand('web', 'open-me', 'browser-1', 'Гость'));

        self::assertTrue($result->isNew);
        self::assertSame(4, $result->attemptsLeft);
        self::assertNotSame('', $result->token);
        self::assertSame($result->participantId, (string) $this->sessions->resolve($result->token));
    }

    #[Test]
    public function a_repeat_visit_reuses_the_same_participant_without_granting_more_attempts(): void
    {
        $first = $this->handler->handle(new OpenSessionCommand('web', 'open-me', 'browser-1', 'Гость'));
        $second = $this->handler->handle(new OpenSessionCommand('web', 'open-me', 'browser-1', 'Гость снова'));

        self::assertFALSE($second->isNew);
        self::assertSame($first->participantId, $second->participantId);
        self::assertSame(4, $second->attemptsLeft);
    }

    #[Test]
    public function an_unknown_channel_is_rejected(): void
    {
        $this->expectException(UseCaseException::class);

        $this->handler->handle(new OpenSessionCommand('fax', 'open-me', 'x', 'Гость'));
    }

    #[Test]
    public function an_unknown_campaign_is_reported_as_not_found(): void
    {
        try {
            $this->handler->handle(new OpenSessionCommand('web', 'missing', 'x', 'Гость'));
            self::fail('Ожидалось «не найдено».');
        } catch (UseCaseException $e) {
            self::assertSame(404, $e->httpStatus);
        }
    }
}
