<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Reward\PromoCode;
use Src\Domain\Reward\PromoCodeBook;

final class InMemoryPromoCodeBook implements PromoCodeBook
{
    /** @var list<array{campaign: string, code: string, issued_to: ?string}> */
    private array $codes = [];

    public function add(CampaignId $campaignId, array $codes): int
    {
        $known = [];
        foreach ($this->codes as $row) {
            if ($row['campaign'] === (string) $campaignId) {
                $known[strtoupper($row['code'])] = true;
            }
        }

        $added = 0;
        foreach ($codes as $code) {
            $upper = strtoupper($code);
            if (isset($known[$upper])) {
                continue;
            }
            $known[$upper] = true;
            $this->codes[] = ['campaign' => (string) $campaignId, 'code' => $code, 'issued_to' => null];
            $added++;
        }

        return $added;
    }

    public function issueNext(CampaignId $campaignId, ParticipantId $participantId): ?PromoCode
    {
        foreach ($this->codes as $index => $row) {
            if ($row['campaign'] === (string) $campaignId && $row['issued_to'] === null) {
                $this->codes[$index]['issued_to'] = (string) $participantId;

                return new PromoCode($row['code'], (string) $participantId, new DateTimeImmutable());
            }
        }

        return null;
    }

    public function remaining(CampaignId $campaignId): int
    {
        return count(array_filter(
            $this->codes,
            static fn (array $row): bool => $row['campaign'] === (string) $campaignId && $row['issued_to'] === null,
        ));
    }

    public function total(CampaignId $campaignId): int
    {
        return count(array_filter(
            $this->codes,
            static fn (array $row): bool => $row['campaign'] === (string) $campaignId,
        ));
    }

    public function clearForCampaign(CampaignId $campaignId): void
    {
        $this->codes = array_values(array_filter(
            $this->codes,
            static fn (array $row): bool => $row['campaign'] !== (string) $campaignId,
        ));
    }
}
