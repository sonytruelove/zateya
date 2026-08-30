<?php

declare(strict_types=1);

namespace Src\Application\Campaign\CreateCampaign;

use DateTimeImmutable;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignPeriod;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Campaign\Slug;
use Src\Domain\Mechanic\MechanicConfig;
use Src\Domain\Mechanic\MechanicConfigRepository;
use Src\Domain\Mechanic\MechanicFactory;
use Src\Domain\Shared\DomainException;

final readonly class CreateCampaignHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private MechanicConfigRepository $mechanicConfigs,
        private MechanicFactory $mechanicFactory,
    ) {
    }

    public function handle(CreateCampaignCommand $command): CampaignCreated
    {
        try {
            $slug = Slug::fromString($command->slug);
            $type = MechanicType::from($command->mechanic);
            $period = CampaignPeriod::between(
                new DateTimeImmutable($command->startsAt),
                new DateTimeImmutable($command->endsAt),
            );
            $theme = CampaignTheme::of($command->colorHex, $command->emoji);
        } catch (DomainException $e) {
            throw UseCaseException::unprocessable('invalid_campaign', $e->getMessage());
        }

        if ($this->campaigns->existsWithSlug($slug)) {
            throw UseCaseException::conflict('slug_taken', "Адрес «{$slug}» уже занят другой кампанией.");
        }

        $id = CampaignId::generate();
        $config = new MechanicConfig($id, $type, $command->mechanicSettings);
        $this->assertMechanicSettingsValid($config);

        $campaign = Campaign::createDraft($id, $slug, $command->title, $type, $period, $theme, $command->attemptsPerParticipant);

        $this->campaigns->save($campaign);
        $this->mechanicConfigs->save($config);

        return new CampaignCreated((string) $id, (string) $slug);
    }

    private function assertMechanicSettingsValid(MechanicConfig $config): void
    {
        try {
            $this->mechanicFactory->fromConfig($config);
        } catch (DomainException $e) {
            throw UseCaseException::unprocessable('invalid_mechanic_settings', $e->getMessage());
        }
    }
}
