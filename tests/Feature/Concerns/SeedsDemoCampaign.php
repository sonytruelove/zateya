<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use Src\Application\Campaign\AddPrizes\AddPrizesCommand;
use Src\Application\Campaign\AddPrizes\AddPrizesHandler;
use Src\Application\Campaign\CreateCampaign\CreateCampaignCommand;
use Src\Application\Campaign\CreateCampaign\CreateCampaignHandler;
use Src\Application\Campaign\PublishCampaign\PublishCampaignCommand;
use Src\Application\Campaign\PublishCampaign\PublishCampaignHandler;
use Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesCommand;
use Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesHandler;

trait SeedsDemoCampaign
{
    /**
     * @param array<string, mixed>|null $settings
     * @return string идентификатор созданной кампании
     */
    protected function seedCampaign(
        string $slug = 'demo',
        string $mechanic = 'wheel',
        int $prizes = 5,
        int $promoCodes = 5,
        bool $publish = true,
        ?array $settings = null,
    ): string {
        $created = app(CreateCampaignHandler::class)->handle(new CreateCampaignCommand(
            slug: $slug,
            title: 'Демонстрационная кампания',
            mechanic: $mechanic,
            startsAt: now()->subDay()->format(DATE_ATOM),
            endsAt: now()->addMonth()->format(DATE_ATOM),
            colorHex: '#0b57d0',
            emoji: 'X',
            attemptsPerParticipant: 3,
            mechanicSettings: $settings ?? $this->defaultSettings($mechanic),
        ));

        if ($publish) {
            app(PublishCampaignHandler::class)->handle(new PublishCampaignCommand($created->campaignId));
        }

        if ($prizes > 0) {
            app(AddPrizesHandler::class)->handle(new AddPrizesCommand($created->campaignId, 'Приз', $prizes));
        }

        if ($promoCodes > 0) {
            $codes = array_map(static fn (int $i): string => "PROMO-{$i}", range(1, $promoCodes));
            app(UploadPromoCodesHandler::class)->handle(new UploadPromoCodesCommand($created->campaignId, $codes));
        }

        return $created->campaignId;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(string $mechanic): array
    {
        if ($mechanic === 'quiz') {
            return [
                'win_threshold' => 10,
                'questions' => [
                    ['id' => 'q1', 'correct_option_id' => 'a', 'points' => 10],
                    ['id' => 'q2', 'correct_option_id' => 'b', 'points' => 10],
                ],
            ];
        }

        // Единственный выигрышный сектор: розыгрыш колеса в тестах детерминирован,
        // исход зависит только от наличия призов в фонде, а не от генератора случайности.
        return [
            'sectors' => [
                ['label' => 'Приз', 'weight' => 1, 'winning' => true, 'points' => 40],
            ],
        ];
    }

    /**
     * @return array{participant_id: string, token: string}
     */
    protected function openWebSession(string $slug = 'demo', string $browserId = 'browser-1'): array
    {
        $response = $this->postJson('/api/v1/participation/sessions', [
            'channel' => 'web',
            'campaign_slug' => $slug,
            'channel_token' => $browserId,
            'display_name' => 'Тестовый игрок',
        ]);

        $response->assertCreated();

        return [
            'participant_id' => $response->json('data.participant_id'),
            'token' => $response->json('data.token'),
        ];
    }
}
