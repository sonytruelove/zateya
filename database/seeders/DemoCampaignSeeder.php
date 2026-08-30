<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Application\Campaign\AddPrizes\AddPrizesCommand;
use Src\Application\Campaign\AddPrizes\AddPrizesHandler;
use Src\Application\Campaign\CreateCampaign\CreateCampaignCommand;
use Src\Application\Campaign\CreateCampaign\CreateCampaignHandler;
use Src\Application\Campaign\PublishCampaign\PublishCampaignCommand;
use Src\Application\Campaign\PublishCampaign\PublishCampaignHandler;
use Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesCommand;
use Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesHandler;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\Slug;

/**
 * Наполняет базу демонстрационной кампанией «Колесо новогодних призов»
 * с призовым фондом и пулом промокодов. Идемпотентен: повторный запуск пропускается.
 */
final class DemoCampaignSeeder extends Seeder
{
    public function run(): void
    {
        /** @var CampaignRepository $campaigns */
        $campaigns = $this->container->make(CampaignRepository::class);
        if ($campaigns->existsWithSlug(Slug::fromString('demo'))) {
            return;
        }

        $created = $this->container->make(CreateCampaignHandler::class)->handle(new CreateCampaignCommand(
            slug: 'demo',
            title: 'Колесо новогодних призов',
            mechanic: 'wheel',
            startsAt: now()->subDay()->format(DATE_ATOM),
            endsAt: now()->addMonth()->format(DATE_ATOM),
            colorHex: '#0b57d0',
            emoji: '🎁',
            attemptsPerParticipant: 5,
            mechanicSettings: [
                'sectors' => [
                    ['label' => 'Пусто', 'weight' => 50, 'winning' => false, 'points' => 5],
                    ['label' => 'Скидка 10%', 'weight' => 30, 'winning' => true, 'points' => 20],
                    ['label' => 'Скидка 30%', 'weight' => 15, 'winning' => true, 'points' => 50],
                    ['label' => 'Главный приз', 'weight' => 5, 'winning' => true, 'points' => 100],
                ],
            ],
        ));

        $this->container->make(PublishCampaignHandler::class)->handle(new PublishCampaignCommand($created->campaignId));

        $this->container->make(AddPrizesHandler::class)->handle(new AddPrizesCommand($created->campaignId, 'Скидочный промокод', 200));
        $this->container->make(AddPrizesHandler::class)->handle(new AddPrizesCommand($created->campaignId, 'Подарочный набор', 10));

        $codes = [];
        for ($i = 1; $i <= 210; $i++) {
            $codes[] = 'NY2026-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        }
        $this->container->make(UploadPromoCodesHandler::class)->handle(new UploadPromoCodesCommand($created->campaignId, $codes));
    }
}
