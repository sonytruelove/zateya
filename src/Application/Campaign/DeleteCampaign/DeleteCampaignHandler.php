<?php

declare(strict_types=1);

namespace Src\Application\Campaign\DeleteCampaign;

use Src\Application\Port\AttemptBalance;
use Src\Application\Port\TransactionManager;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Leaderboard\LeaderboardStore;
use Src\Domain\Mechanic\MechanicConfigRepository;
use Src\Domain\Participation\AttemptRepository;
use Src\Domain\Participation\ParticipantRepository;
use Src\Domain\Reward\PrizePool;
use Src\Domain\Reward\PromoCodeBook;

/**
 * Удаляет кампанию вместе со всеми связанными данными: механикой, призовым фондом,
 * промокодами, попытками, участниками и внешними следами в Redis (рейтинг, балансы).
 */
final readonly class DeleteCampaignHandler
{
    public function __construct(
        private CampaignRepository $campaigns,
        private MechanicConfigRepository $mechanicConfigs,
        private AttemptRepository $attempts,
        private ParticipantRepository $participants,
        private PrizePool $prizePool,
        private PromoCodeBook $promoCodes,
        private LeaderboardStore $leaderboard,
        private AttemptBalance $balance,
        private TransactionManager $tx,
    ) {
    }

    public function handle(DeleteCampaignCommand $command): void
    {
        $id = CampaignId::fromString($command->campaignId);
        try {
            $this->campaigns->byId($id);
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        $this->tx->transactional(function () use ($id): void {
            $this->attempts->deleteForCampaign($id);
            $this->participants->deleteForCampaign($id);
            $this->prizePool->clearForCampaign($id);
            $this->promoCodes->clearForCampaign($id);
            $this->mechanicConfigs->deleteForCampaign($id);
            $this->campaigns->delete($id);
        });

        $this->leaderboard->clear($id);
        $this->balance->reset($id);
    }
}
