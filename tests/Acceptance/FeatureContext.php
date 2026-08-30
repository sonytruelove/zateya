<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Behat\Behat\Context\Context;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Src\Application\Campaign\AddPrizes\AddPrizesCommand;
use Src\Application\Campaign\AddPrizes\AddPrizesHandler;
use Src\Application\Campaign\CreateCampaign\CreateCampaignCommand;
use Src\Application\Campaign\CreateCampaign\CreateCampaignHandler;
use Src\Application\Campaign\PublishCampaign\PublishCampaignCommand;
use Src\Application\Campaign\PublishCampaign\PublishCampaignHandler;
use Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesCommand;
use Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesHandler;
use Src\Application\Leaderboard\ViewLeaderboard\ViewLeaderboardHandler;
use Src\Application\Leaderboard\ViewLeaderboard\ViewLeaderboardQuery;
use Src\Application\Participation\MyRewards\MyRewardsHandler;
use Src\Application\Participation\MyRewards\MyRewardsQuery;
use Src\Application\Participation\OpenSession\OpenSessionCommand;
use Src\Application\Participation\OpenSession\OpenSessionHandler;
use Src\Application\Participation\PlayAttempt\PlayAttemptCommand;
use Src\Application\Participation\PlayAttempt\PlayAttemptHandler;
use Src\Application\Participation\PlayAttempt\PlayResult;
use Src\Application\Shared\UseCaseException;
use Webmozart\Assert\Assert;

/**
 * Шаги приёмочных сценариев: работают через сценарии использования прикладного слоя
 * поверх настоящего контейнера Laravel и базы в памяти.
 */
final class FeatureContext implements Context
{
    private Application $app;
    private string $campaignId = '';
    private string $participantId = '';
    private ?PlayResult $lastResult = null;
    private ?UseCaseException $lastError = null;

    private static ?Application $sharedApp = null;

    /**
     * @BeforeScenario
     */
    public function bootApplication(): void
    {
        if (self::$sharedApp === null) {
            $app = require __DIR__ . '/../../bootstrap/app.php';
            $app->make(ConsoleKernel::class)->bootstrap();
            self::$sharedApp = $app;
        }

        $this->app = self::$sharedApp;
        $this->app->make(ConsoleKernel::class)->call('migrate:fresh', ['--force' => true]);
        $this->campaignId = '';
        $this->participantId = '';
        $this->lastResult = null;
        $this->lastError = null;
    }

    /**
     * @Given организатор создал и опубликовал кампанию :slug с механикой :mechanic, попыток на участника: :attempts
     */
    public function organiserPublishedCampaign(string $slug, string $mechanic, int $attempts): void
    {
        $created = $this->app->make(CreateCampaignHandler::class)->handle(new CreateCampaignCommand(
            slug: $slug,
            title: 'Приёмочная кампания',
            mechanic: $mechanic,
            startsAt: now()->subDay()->format(DATE_ATOM),
            endsAt: now()->addMonth()->format(DATE_ATOM),
            colorHex: '#0b57d0',
            emoji: 'X',
            attemptsPerParticipant: $attempts,
            mechanicSettings: $mechanic === 'quiz'
                ? ['win_threshold' => 10, 'questions' => [['id' => 'q1', 'correct_option_id' => 'a', 'points' => 10]]]
                : ['sectors' => [['label' => 'Приз', 'weight' => 1, 'winning' => true, 'points' => 40]]],
        ));
        $this->campaignId = $created->campaignId;

        $this->app->make(PublishCampaignHandler::class)->handle(new PublishCampaignCommand($this->campaignId));
    }

    /**
     * @Given в призовой фонд кампании :slug добавлено :count шт. приза :title
     */
    public function prizePoolHas(string $slug, int $count, string $title): void
    {
        $this->app->make(AddPrizesHandler::class)->handle(new AddPrizesCommand($this->campaignId, $title, $count));
    }

    /**
     * @Given для кампании :slug загружен пул промокодов, штук: :count
     */
    public function promoCodesUploaded(string $slug, int $count): void
    {
        $codes = array_map(static fn (int $i): string => "ACC-{$i}", range(1, $count));
        $this->app->make(UploadPromoCodesHandler::class)->handle(new UploadPromoCodesCommand($this->campaignId, $codes));
    }

    /**
     * @When участник :browser открывает сессию в кампании :slug
     */
    public function participantOpensSession(string $browser, string $slug): void
    {
        $session = $this->app->make(OpenSessionHandler::class)->handle(
            new OpenSessionCommand('web', $slug, $browser, 'Игрок ' . $browser),
        );
        $this->participantId = $session->participantId;
    }

    /**
     * @When участник разыгрывает попытку в кампании :slug
     */
    public function participantPlays(string $slug): void
    {
        $this->lastError = null;
        try {
            $this->lastResult = $this->app->make(PlayAttemptHandler::class)->handle(
                new PlayAttemptCommand($slug, $this->participantId, ['answers' => [['question_id' => 'q1', 'option_id' => 'a']]]),
            );
        } catch (UseCaseException $e) {
            $this->lastError = $e;
        }
    }

    /**
     * @When участник разыгрывает попытку в кампании :slug, повторов: :times
     */
    public function participantPlaysMore(string $slug, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->participantPlays($slug);
        }
    }

    /**
     * @Then результат розыгрыша — выигрыш
     */
    public function heWins(): void
    {
        Assert::isInstanceOf($this->lastResult, PlayResult::class);
        Assert::true($this->lastResult->won, 'Ожидался выигрыш.');
    }

    /**
     * @Then участнику выдан промокод
     */
    public function heGetsPromoCode(): void
    {
        Assert::notNull($this->lastResult?->promoCode, 'Ожидался выданный промокод.');
    }

    /**
     * @Then промокод участнику не выдан
     */
    public function noPromoCode(): void
    {
        Assert::null($this->lastResult?->promoCode, 'Промокод не должен быть выдан.');
    }

    /**
     * @Then остаток попыток участника: :count
     */
    public function attemptsLeft(int $count): void
    {
        Assert::same($this->lastResult?->attemptsLeft, $count);
    }

    /**
     * @Then попытка отклонена с кодом :code
     */
    public function attemptRejected(string $code): void
    {
        Assert::notNull($this->lastError, 'Ожидалась отклонённая попытка.');
        Assert::same($this->lastError->errorCode, $code);
    }

    /**
     * @Then участник занимает :rank место в рейтинге кампании :slug
     */
    public function participantRank(int $rank, string $slug): void
    {
        $board = $this->app->make(ViewLeaderboardHandler::class)->handle(
            new ViewLeaderboardQuery($slug, $this->participantId, 10),
        );
        Assert::notNull($board->me, 'Участник должен быть в рейтинге.');
        Assert::same($board->me->rank, $rank);
    }

    /**
     * @Then наград у участника в кампании :slug: :count
     */
    public function rewardsCount(string $slug, int $count): void
    {
        $rewards = $this->app->make(MyRewardsHandler::class)->handle(new MyRewardsQuery($slug, $this->participantId));
        Assert::count($rewards, $count);
    }
}
