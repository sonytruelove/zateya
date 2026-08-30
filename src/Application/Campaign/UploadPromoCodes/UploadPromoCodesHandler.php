<?php

declare(strict_types=1);

namespace Src\Application\Campaign\UploadPromoCodes;

use Src\Application\Shared\UseCaseException;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Campaign\CampaignNotFound;
use Src\Domain\Campaign\CampaignRepository;
use Src\Domain\Reward\PromoCodeBook;

final readonly class UploadPromoCodesHandler
{
    private const CODE_PATTERN = '/^[A-Za-z0-9._-]{3,64}$/';
    private const MAX_BATCH = 50_000;

    public function __construct(
        private CampaignRepository $campaigns,
        private PromoCodeBook $promoCodes,
    ) {
    }

    /**
     * @return array{added:int, skipped:int}
     */
    public function handle(UploadPromoCodesCommand $command): array
    {
        $clean = $this->normalize($command->codes);
        if ($clean === []) {
            throw UseCaseException::unprocessable('no_valid_codes', 'В загрузке нет ни одного корректного кода.');
        }

        $id = CampaignId::fromString($command->campaignId);
        try {
            $this->campaigns->byId($id);
        } catch (CampaignNotFound $e) {
            throw UseCaseException::notFound($e->getMessage());
        }

        $added = $this->promoCodes->add($id, $clean);

        return ['added' => $added, 'skipped' => count($command->codes) - $added];
    }

    /**
     * @param list<string> $codes
     * @return list<string>
     */
    private function normalize(array $codes): array
    {
        $seen = [];
        foreach ($codes as $raw) {
            $code = trim($raw);
            if (preg_match(self::CODE_PATTERN, $code) === 1) {
                $seen[strtoupper($code)] = $code;
            }
            if (count($seen) >= self::MAX_BATCH) {
                break;
            }
        }

        return array_values($seen);
    }
}
