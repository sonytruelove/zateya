<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Presentation\Http\SnakeArray;

final class SnakeArrayTest extends TestCase
{
    #[Test]
    public function it_converts_camel_case_keys_at_every_depth(): void
    {
        $result = SnakeArray::from([
            'campaignId' => '1',
            'attemptsLeft' => 3,
            'nested' => ['prizeTitle' => 'X', 'list' => [['promoCode' => 'A']]],
        ]);

        self::assertSame(
            [
                'campaign_id' => '1',
                'attempts_left' => 3,
                'nested' => ['prize_title' => 'X', 'list' => [['promo_code' => 'A']]],
            ],
            $result,
        );
    }

    #[Test]
    public function it_leaves_list_indexes_and_snake_keys_untouched(): void
    {
        self::assertSame(['a_b' => 1, 0 => 2], SnakeArray::from(['a_b' => 1, 0 => 2]));
    }
}
