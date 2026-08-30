<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use Src\Application\Port\AttemptBalance;
use Src\Application\Port\EventPublisher;
use Src\Application\Port\ParticipantSessions;
use Src\Application\Port\RateLimiter;
use Src\Application\Port\RealtimePublisher;
use Src\Application\Port\TransactionManager;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Leaderboard\LeaderboardStore;
use Src\Domain\Mechanic\MechanicConfigRepository;
use Src\Domain\Mechanic\RandomSource;
use Src\Domain\Participation\AttemptRepository;
use Src\Domain\Participation\ParticipantRepository;
use Src\Domain\Reward\PrizePool;
use Src\Domain\Reward\PromoCodeBook;
use Src\Domain\Reward\RewardLedger;
use Src\Domain\Shared\Clock;
use Src\Infrastructure\Balance\ArrayAttemptBalance;
use Src\Infrastructure\Balance\RedisAttemptBalance;
use Src\Infrastructure\Clock\SystemClock;
use Src\Infrastructure\Leaderboard\ArrayLeaderboardStore;
use Src\Infrastructure\Leaderboard\RedisLeaderboardStore;
use Src\Infrastructure\Messaging\LogEventPublisher;
use Src\Infrastructure\Messaging\RabbitMqEventPublisher;
use Src\Infrastructure\Persistence\Eloquent\DatabaseTransactionManager;
use Src\Infrastructure\Persistence\Eloquent\EloquentAttemptRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentCampaignRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentMechanicConfigRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentParticipantRepository;
use Src\Infrastructure\Persistence\Eloquent\EloquentPrizePool;
use Src\Infrastructure\Persistence\Eloquent\EloquentPromoCodeBook;
use Src\Infrastructure\Persistence\Eloquent\EloquentRewardLedger;
use Src\Infrastructure\RateLimit\ArrayRateLimiter;
use Src\Infrastructure\RateLimit\RedisRateLimiter;
use Src\Infrastructure\Realtime\CentrifugoPublisher;
use Src\Infrastructure\Realtime\NullRealtimePublisher;
use Src\Infrastructure\Session\ArrayParticipantSessions;
use Src\Infrastructure\Session\RedisParticipantSessions;

/**
 * Связывает интерфейсы чистой архитектуры с реализациями инфраструктуры.
 * Выбор Redis/память и включение Centrifugo/RabbitMQ управляется config/zateya.php.
 */
final class DomainServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        Clock::class => SystemClock::class,
        RandomSource::class => \Src\Domain\Mechanic\SystemRandomSource::class,
        CampaignRepository::class => EloquentCampaignRepository::class,
        MechanicConfigRepository::class => EloquentMechanicConfigRepository::class,
        ParticipantRepository::class => EloquentParticipantRepository::class,
        AttemptRepository::class => EloquentAttemptRepository::class,
        PrizePool::class => EloquentPrizePool::class,
        PromoCodeBook::class => EloquentPromoCodeBook::class,
        RewardLedger::class => EloquentRewardLedger::class,
        TransactionManager::class => DatabaseTransactionManager::class,
    ];

    public function register(): void
    {
        $this->bindLeaderboard();
        $this->bindBalance();
        $this->bindRateLimiter();
        $this->bindSessions();
        $this->bindRealtime();
        $this->bindMessaging();
        $this->bindPresentation();
    }

    private function bindPresentation(): void
    {
        $slug = static fn (): string => (string) config('zateya.default_campaign_slug', 'demo');

        $this->app->when(\Src\Presentation\Http\Middleware\EnsureAdminToken::class)
            ->needs('$expectedToken')
            ->give(static fn (): string => (string) config('zateya.admin_token', ''));

        $this->app->when(\Src\Presentation\Channel\Telegram\TelegramWebhookController::class)
            ->needs('$webhookSecret')
            ->give(static fn (): string => (string) config('zateya.telegram.webhook_secret', ''));
        $this->app->when(\Src\Presentation\Channel\Telegram\TelegramWebhookController::class)
            ->needs('$defaultCampaignSlug')->give($slug);

        $this->app->when(\Src\Presentation\Channel\Vk\VkCallbackController::class)
            ->needs('$confirmationToken')
            ->give(static fn (): string => (string) config('zateya.vk.confirmation_token', ''));
        $this->app->when(\Src\Presentation\Channel\Vk\VkCallbackController::class)
            ->needs('$secretKey')
            ->give(static fn (): string => (string) config('zateya.vk.secret_key', ''));
        $this->app->when(\Src\Presentation\Channel\Vk\VkCallbackController::class)
            ->needs('$defaultCampaignSlug')->give($slug);

        $this->app->when(\Src\Presentation\Channel\Vk\VkCallbackSignature::class)
            ->needs('$appSecret')
            ->give(static fn (): string => (string) config('zateya.vk.app_secret', ''));
    }

    private function driver(string $name): string
    {
        return (string) config("zateya.drivers.{$name}", 'array');
    }

    private function redisConnection(): string
    {
        return (string) config('zateya.drivers.redis_connection', 'default');
    }

    private function bindLeaderboard(): void
    {
        $this->app->singleton(LeaderboardStore::class, fn ($app): LeaderboardStore => $this->driver('leaderboard') === 'redis'
            ? new RedisLeaderboardStore($app->make(RedisFactory::class), $this->redisConnection())
            : new ArrayLeaderboardStore());
    }

    private function bindBalance(): void
    {
        $this->app->singleton(AttemptBalance::class, fn ($app): AttemptBalance => $this->driver('balance') === 'redis'
            ? new RedisAttemptBalance($app->make(RedisFactory::class), $this->redisConnection())
            : new ArrayAttemptBalance());
    }

    private function bindRateLimiter(): void
    {
        $this->app->singleton(RateLimiter::class, fn ($app): RateLimiter => $this->driver('rate_limiter') === 'redis'
            ? new RedisRateLimiter($app->make(RedisFactory::class), $this->redisConnection())
            : new ArrayRateLimiter());
    }

    private function bindSessions(): void
    {
        $this->app->singleton(ParticipantSessions::class, fn ($app): ParticipantSessions => $this->driver('sessions') === 'redis'
            ? new RedisParticipantSessions($app->make(RedisFactory::class), $this->redisConnection())
            : new ArrayParticipantSessions());
    }

    private function bindRealtime(): void
    {
        $this->app->singleton(RealtimePublisher::class, function ($app): RealtimePublisher {
            if (!config('zateya.realtime.enabled')) {
                return new NullRealtimePublisher();
            }

            return new CentrifugoPublisher(
                $app->make(\Illuminate\Http\Client\Factory::class),
                $app->make(LoggerInterface::class),
                (string) config('zateya.realtime.api_url'),
                (string) config('zateya.realtime.api_key'),
            );
        });
    }

    private function bindMessaging(): void
    {
        $this->app->singleton(EventPublisher::class, function ($app): EventPublisher {
            if (!config('zateya.messaging.enabled')) {
                return new LogEventPublisher($app->make(LoggerInterface::class));
            }

            $connection = new AMQPStreamConnection(
                (string) config('zateya.messaging.host'),
                (int) config('zateya.messaging.port'),
                (string) config('zateya.messaging.user'),
                (string) config('zateya.messaging.password'),
                (string) config('zateya.messaging.vhost'),
            );

            return new RabbitMqEventPublisher($connection, $app->make(LoggerInterface::class), (string) config('zateya.messaging.exchange'));
        });
    }
}
