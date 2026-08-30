<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Application\Campaign\CreateCampaign\CreateCampaignCommand;
use Src\Application\Campaign\CreateCampaign\CreateCampaignHandler;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Domain\Mechanic\MechanicFactory;
use Tests\Support\InMemoryCampaignRepository;
use Tests\Support\InMemoryMechanicConfigRepository;

final class CreateCampaignHandlerTest extends TestCase
{
    private InMemoryCampaignRepository $campaigns;
    private InMemoryMechanicConfigRepository $configs;
    private CreateCampaignHandler $handler;

    protected function setUp(): void
    {
        $this->campaigns = new InMemoryCampaignRepository();
        $this->configs = new InMemoryMechanicConfigRepository();
        $this->handler = new CreateCampaignHandler($this->campaigns, $this->configs, new MechanicFactory());
    }

    #[Test]
    public function it_stores_a_draft_campaign_with_its_mechanic_configuration(): void
    {
        $result = $this->handler->handle($this->command('summer-wheel'));

        $campaign = $this->campaigns->bySlug(Slug::fromString('summer-wheel'));
        self::assertSame($result->campaignId, (string) $campaign->id);
        self::assertSame(MechanicType::Wheel, $campaign->mechanic);
        self::assertSame(MechanicType::Wheel, $this->configs->forCampaign($campaign->id)->type);
    }

    #[Test]
    public function a_duplicate_slug_is_rejected(): void
    {
        $this->handler->handle($this->command('summer-wheel'));

        $this->expectException(UseCaseException::class);
        $this->expectExceptionMessage('уже занят');
        $this->handler->handle($this->command('summer-wheel'));
    }

    #[Test]
    public function an_end_before_the_start_is_rejected_as_unprocessable(): void
    {
        try {
            $this->handler->handle($this->command('bad-period', startsAt: '2026-05-01', endsAt: '2026-04-01'));
            self::fail('Ожидалось исключение о некорректном периоде.');
        } catch (UseCaseException $e) {
            self::assertSame(422, $e->httpStatus);
        }
    }

    #[Test]
    public function broken_mechanic_settings_are_rejected(): void
    {
        $this->expectException(UseCaseException::class);
        $this->expectExceptionMessage('нет секторов');

        $this->handler->handle($this->command('empty-wheel', settings: ['sectors' => []]));
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    private function command(
        string $slug,
        string $startsAt = '2026-04-01',
        string $endsAt = '2026-05-01',
        ?array $settings = null,
    ): CreateCampaignCommand {
        return new CreateCampaignCommand(
            slug: $slug,
            title: 'Тестовая кампания',
            mechanic: 'wheel',
            startsAt: $startsAt,
            endsAt: $endsAt,
            colorHex: '#0b57d0',
            emoji: 'X',
            attemptsPerParticipant: 3,
            mechanicSettings: $settings ?? ['sectors' => [
                ['label' => 'Приз', 'weight' => 1, 'winning' => true, 'points' => 10],
            ]],
        );
    }
}
