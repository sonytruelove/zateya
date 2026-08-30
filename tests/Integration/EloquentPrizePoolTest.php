<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Src\Infrastructure\Persistence\Eloquent\EloquentPrizePool;
use Tests\Integration\Concerns\PersistsCampaign;
use Tests\TestCase;

/**
 * Проверяет атомарность призового фонда: условный UPDATE «remaining = remaining - 1
 * WHERE remaining > 0» никогда не уводит остаток ниже нуля, сколько бы раз его ни звали.
 */
final class EloquentPrizePoolTest extends TestCase
{
    use RefreshDatabase;
    use PersistsCampaign;

    #[Test]
    public function reserving_past_exhaustion_never_drives_the_remainder_below_zero(): void
    {
        $pool = new EloquentPrizePool(DB::connection());
        $campaignId = $this->persistCampaign();
        $pool->addPrize($campaignId, 'Приз', 5);

        $reserved = 0;
        for ($i = 0; $i < 25; $i++) {
            if ($pool->reserveOne($campaignId) !== null) {
                $reserved++;
            }
        }

        self::assertSame(5, $reserved);
        self::assertSame(0, $pool->remaining($campaignId));
        self::assertGreaterThanOrEqual(0, (int) DB::table('prizes')->where('campaign_id', (string) $campaignId)->min('remaining'));
    }

    #[Test]
    public function it_prefers_the_position_with_the_larger_remainder(): void
    {
        $pool = new EloquentPrizePool(DB::connection());
        $campaignId = $this->persistCampaign();
        $small = $pool->addPrize($campaignId, 'Малый фонд', 1);
        $large = $pool->addPrize($campaignId, 'Большой фонд', 10);

        $first = $pool->reserveOne($campaignId);

        self::assertNotNull($first);
        self::assertSame('Большой фонд', $first->title);
        self::assertSame(9, (int) DB::table('prizes')->where('id', (string) $large)->value('remaining'));
        self::assertSame(1, (int) DB::table('prizes')->where('id', (string) $small)->value('remaining'));
    }

    #[Test]
    public function release_returns_a_unit_to_the_pool(): void
    {
        $pool = new EloquentPrizePool(DB::connection());
        $campaignId = $this->persistCampaign();
        $id = $pool->addPrize($campaignId, 'Приз', 2);

        $pool->reserveOne($campaignId);
        $pool->releaseOne($id);

        self::assertSame(2, $pool->remaining($campaignId));
    }
}
