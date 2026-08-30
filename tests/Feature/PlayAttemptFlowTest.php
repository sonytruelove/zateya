<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SeedsDemoCampaign;
use Tests\TestCase;

final class PlayAttemptFlowTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDemoCampaign;

    #[Test]
    public function a_participant_opens_a_session_plays_and_appears_in_the_leaderboard(): void
    {
        $this->seedCampaign(mechanic: 'wheel');
        $session = $this->openWebSession();

        $play = $this->withHeader('Authorization', "Bearer {$session['token']}")
            ->postJson('/api/v1/campaigns/demo/attempts', ['move' => []]);

        $play->assertOk()
            ->assertJsonPath('data.won', true)
            ->assertJsonPath('data.attempts_left', 2);
        self::assertNotNull($play->json('data.promo_code'));

        $board = $this->getJson('/api/v1/campaigns/demo/leaderboard')->assertOk();
        self::assertSame('Тестовый игрок', $board->json('data.entries.0.display_name'));
    }

    #[Test]
    public function attempts_run_out_after_the_configured_number(): void
    {
        $this->seedCampaign(mechanic: 'wheel', prizes: 0, promoCodes: 0);
        $session = $this->openWebSession();
        $auth = ['Authorization' => "Bearer {$session['token']}"];

        for ($i = 0; $i < 3; $i++) {
            $this->withHeaders($auth)->postJson('/api/v1/campaigns/demo/attempts', ['move' => []])->assertOk();
        }

        $this->withHeaders($auth)->postJson('/api/v1/campaigns/demo/attempts', ['move' => []])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'no_attempts_left');
    }

    #[Test]
    public function a_win_beyond_the_prize_pool_returns_a_consolation_result(): void
    {
        $this->seedCampaign(mechanic: 'wheel', prizes: 1, promoCodes: 1);
        $first = $this->openWebSession('demo', 'browser-a');
        $second = $this->openWebSession('demo', 'browser-b');

        $this->withHeader('Authorization', "Bearer {$first['token']}")
            ->postJson('/api/v1/campaigns/demo/attempts', ['move' => []])
            ->assertOk()->assertJsonPath('data.prize_title', 'Приз');

        $this->withHeader('Authorization', "Bearer {$second['token']}")
            ->postJson('/api/v1/campaigns/demo/attempts', ['move' => []])
            ->assertOk()
            ->assertJsonPath('data.won', true)
            ->assertJsonPath('data.prize_title', null);
    }
}
