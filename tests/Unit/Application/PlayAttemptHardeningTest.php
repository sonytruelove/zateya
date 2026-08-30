<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Application\Participation\PlayAttempt\PlayAttemptCommand;
use Src\Application\Participation\PlayAttempt\PlayAttemptHandler;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Domain\Mechanic\MechanicConfig;
use Src\Domain\Mechanic\MechanicFactory;
use Src\Domain\Participation\Channel;
use Src\Domain\Participation\ChannelIdentity;
use Src\Domain\Participation\Participant;
use Src\Domain\Participation\ParticipantId;
use Src\Infrastructure\Balance\ArrayAttemptBalance;
use Src\Infrastructure\Leaderboard\ArrayLeaderboardStore;
use Src\Infrastructure\Messaging\InMemoryEventPublisher;
use Tests\Support\FixedClock;
use Tests\Support\ImmediateTransactionManager;
use Tests\Support\InMemoryAttemptRepository;
use Tests\Support\InMemoryCampaignRepository;
use Tests\Support\InMemoryMechanicConfigRepository;
use Tests\Support\InMemoryParticipantRepository;
use Tests\Support\InMemoryPrizePool;
use Tests\Support\InMemoryPromoCodeBook;
use Tests\Support\RecordingRealtimePublisher;
use Tests\Support\SequenceRandomSource;

/**
 * Проверки, закрывающие «выживающих мутантов» в центральном сценарии:
 * порядок проверок, публикация в рейтинг и персональное уведомление,
 * принадлежность участника кампании.
 */
final class PlayAttemptHardeningTest extends TestCase
{
    private InMemoryCampaignRepository $campaigns;
    private InMemoryMechanicConfigRepository $configs;
    private InMemoryParticipantRepository $participants;
    private ArrayAttemptBalance $balance;
    private ArrayLeaderboardStore $leaderboard;
    private RecordingRealtimePublisher $realtime;
    private InMemoryPrizePool $prizePool;
    private InMemoryPromoCodeBook $promoCodes;
    private CampaignId $quizId;
    private ParticipantId $participantId;

    protected function setUp(): void
    {
        $this->campaigns = new InMemoryCampaignRepository();
        $this->configs = new InMemoryMechanicConfigRepository();
        $this->participants = new InMemoryParticipantRepository();
        $this->balance = new ArrayAttemptBalance();
        $this->leaderboard = new ArrayLeaderboardStore();
        $this->realtime = new RecordingRealtimePublisher();
        $this->prizePool = new InMemoryPrizePool();
        $this->promoCodes = new InMemoryPromoCodeBook();

        $this->quizId = CampaignId::generate();
        $quiz = Campaign::createDraft(
            $this->quizId,
            Slug::fromString('hard-quiz'),
            'Викторина проверок',
            MechanicType::Quiz,
            CampaignPeriod::between(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31')),
            CampaignTheme::default(),
            3,
        );
        $quiz->publish(new DateTimeImmutable('2026-02-01'));
        $this->campaigns->save($quiz);
        $this->configs->save(new MechanicConfig($this->quizId, MechanicType::Quiz, [
            'win_threshold' => 10,
            'questions' => [['id' => 'q1', 'correct_option_id' => 'a', 'points' => 10]],
        ]));

        $this->participantId = ParticipantId::generate();
        $this->participants->save(Participant::register(
            $this->participantId,
            $this->quizId,
            ChannelIdentity::of(Channel::Web, 'browser-hard'),
            'Проверяющий',
            new DateTimeImmutable('2026-02-01'),
        ));
        $this->balance->grant($this->quizId, $this->participantId, 3);
    }

    #[Test]
    public function an_invalid_move_is_rejected_before_a_single_attempt_is_spent(): void
    {
        try {
            $this->handler()->handle(new PlayAttemptCommand('hard-quiz', (string) $this->participantId, ['answers' => [['question_id' => 'nope', 'option_id' => 'a']]]));
            self::fail('Ожидалась ошибка некорректного хода.');
        } catch (UseCaseException $e) {
            self::assertSame('invalid_move', $e->errorCode);
        }

        self::assertSame(3, $this->balance->remaining($this->quizId, $this->participantId));
    }

    #[Test]
    public function a_played_attempt_pushes_the_new_leaderboard_to_the_campaign_channel(): void
    {
        $this->handler()->handle(new PlayAttemptCommand('hard-quiz', (string) $this->participantId, ['answers' => [['question_id' => 'q1', 'option_id' => 'a']]]));

        $channels = array_column($this->realtime->messages, 'channel');
        self::assertContains('campaign:hard-quiz:leaderboard', $channels);
    }

    #[Test]
    public function a_prize_win_also_sends_a_personal_notification_but_a_plain_win_does_not(): void
    {
        $this->handler()->handle(new PlayAttemptCommand('hard-quiz', (string) $this->participantId, ['answers' => [['question_id' => 'q1', 'option_id' => 'a']]]));
        self::assertNotContains('prize_awarded', $this->realtime->typesForParticipant());

        $this->prizePool->addPrize($this->quizId, 'Подарок', 1);
        $this->handler()->handle(new PlayAttemptCommand('hard-quiz', (string) $this->participantId, ['answers' => [['question_id' => 'q1', 'option_id' => 'a']]]));
        self::assertContains('prize_awarded', $this->realtime->typesForParticipant());
    }

    #[Test]
    public function a_losing_move_keeps_the_participant_at_zero_score_on_the_board(): void
    {
        $this->handler()->handle(new PlayAttemptCommand('hard-quiz', (string) $this->participantId, ['answers' => [['question_id' => 'q1', 'option_id' => 'wrong']]]));

        $top = $this->leaderboard->top($this->quizId, 10);
        self::assertCount(1, $top);
        self::assertSame(0, $top[0]->score);
    }

    private function handler(): PlayAttemptHandler
    {
        return new PlayAttemptHandler(
            $this->campaigns,
            $this->configs,
            new MechanicFactory(),
            $this->participants,
            new InMemoryAttemptRepository(),
            $this->balance,
            $this->prizePool,
            $this->promoCodes,
            $this->leaderboard,
            new InMemoryEventPublisher(),
            $this->realtime,
            new ImmediateTransactionManager(),
            new SequenceRandomSource(1),
            FixedClock::at('2026-06-01'),
        );
    }
}
