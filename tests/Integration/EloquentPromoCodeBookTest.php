<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Src\Domain\Participation\ParticipantId;
use Src\Infrastructure\Persistence\Eloquent\EloquentPromoCodeBook;
use Tests\Integration\Concerns\PersistsCampaign;
use Tests\TestCase;

final class EloquentPromoCodeBookTest extends TestCase
{
    use RefreshDatabase;
    use PersistsCampaign;

    #[Test]
    public function it_adds_codes_once_and_skips_case_insensitive_duplicates(): void
    {
        $book = new EloquentPromoCodeBook(DB::connection());
        $campaignId = $this->persistCampaign();

        $first = $book->add($campaignId, ['abc-1', 'abc-2']);
        $second = $book->add($campaignId, ['ABC-1', 'abc-3']);

        self::assertSame(2, $first);
        self::assertSame(1, $second);
        self::assertSame(3, $book->total($campaignId));
    }

    #[Test]
    public function each_code_is_issued_to_exactly_one_participant_and_then_the_book_is_empty(): void
    {
        $book = new EloquentPromoCodeBook(DB::connection());
        $campaignId = $this->persistCampaign();
        $book->add($campaignId, ['C-1', 'C-2', 'C-3']);

        $issued = [];
        for ($i = 0; $i < 10; $i++) {
            $code = $book->issueNext($campaignId, ParticipantId::generate());
            if ($code !== null) {
                $issued[] = $code->code;
            }
        }

        self::assertSame(['C-1', 'C-2', 'C-3'], $issued);
        self::assertSame(0, $book->remaining($campaignId));
        self::assertSame(0, DB::table('promo_codes')
            ->where('campaign_id', (string) $campaignId)
            ->whereNull('issued_to_participant_id')
            ->count());
    }
}
