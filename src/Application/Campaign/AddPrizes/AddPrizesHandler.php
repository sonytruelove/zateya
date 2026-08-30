<?php

declare(strict_types=1);

namespace Src\Application\Campaign\AddPrizes;

use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Reward\PrizePool;

final readonly class AddPrizesHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private PrizePool $prizePool,
    ) {
    }

    public function handle(AddPrizesCommand $command): int
    {
        if ($command->quantity < 1 || $command->quantity > 1_000_000) {
            throw UseCaseException::unprocessable('invalid_quantity', "Количество {$command->quantity} вне диапазона 1..1000000.");
        }

        $title = trim($command->title);
        if (mb_strlen($title) < 2 || mb_strlen($title) > 120) {
            throw UseCaseException::unprocessable('invalid_prize_title', 'Название приза от 2 до 120 символов.');
        }

        $id = CampaignId::fromString($command->campaignId);
        try {
            $this->campaigns->byId($id);
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        $this->prizePool->addPrize($id, $title, $command->quantity);

        return $this->prizePool->remaining($id);
    }
}
