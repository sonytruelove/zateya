<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Src\Domain\Campaign\CampaignId;
use Src\Domain\Participation\ParticipantId;
use Src\Domain\Reward\PromoCode;
use Src\Domain\Reward\PromoCodeBook;

/**
 * Пул промокодов поверх таблицы promo_codes. Выдача следующего кода атомарна:
 * помечающий UPDATE «... WHERE id = ? AND issued_to_participant_id IS NULL» проходит
 * ровно у одного из параллельных запросов, остальные повторяют выбор.
 */
final class EloquentPromoCodeBook implements PromoCodeBook
{
    private const ISSUE_RETRIES = 8;

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function add(CampaignId $campaignId, array $codes): int
    {
        $existing = $this->db->table('promo_codes')
            ->where('campaign_id', (string) $campaignId)
            ->pluck('code_upper')
            ->all();
        $known = array_fill_keys(array_map('strval', $existing), true);

        $rows = [];
        foreach ($codes as $code) {
            $upper = strtoupper($code);
            if (isset($known[$upper])) {
                continue;
            }
            $known[$upper] = true;
            $rows[] = ['campaign_id' => (string) $campaignId, 'code' => $code, 'code_upper' => $upper];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->db->table('promo_codes')->insert($chunk);
        }

        return count($rows);
    }

    public function issueNext(CampaignId $campaignId, ParticipantId $participantId): ?PromoCode
    {
        for ($attempt = 0; $attempt < self::ISSUE_RETRIES; $attempt++) {
            $candidate = $this->db->table('promo_codes')
                ->where('campaign_id', (string) $campaignId)
                ->whereNull('issued_to_participant_id')
                ->orderBy('id')
                ->first(['id', 'code']);

            if ($candidate === null) {
                return null;
            }

            $issuedAt = new DateTimeImmutable();
            $applied = $this->db->table('promo_codes')
                ->where('id', $candidate->id)
                ->whereNull('issued_to_participant_id')
                ->update([
                    'issued_to_participant_id' => (string) $participantId,
                    'issued_at' => $issuedAt->format('Y-m-d H:i:sP'),
                ]);

            if ($applied === 1) {
                return new PromoCode((string) $candidate->code, (string) $participantId, $issuedAt);
            }
        }

        return null;
    }

    public function remaining(CampaignId $campaignId): int
    {
        return $this->db->table('promo_codes')
            ->where('campaign_id', (string) $campaignId)
            ->whereNull('issued_to_participant_id')
            ->count();
    }

    public function total(CampaignId $campaignId): int
    {
        return $this->db->table('promo_codes')->where('campaign_id', (string) $campaignId)->count();
    }

    public function clearForCampaign(CampaignId $campaignId): void
    {
        $this->db->table('promo_codes')->where('campaign_id', (string) $campaignId)->delete();
    }
}
