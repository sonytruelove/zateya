<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SeedsDemoCampaign;
use Tests\TestCase;

final class PublicReadEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDemoCampaign;

    #[Test]
    public function the_campaign_card_exposes_theme_and_period(): void
    {
        $this->seedCampaign('demo', 'quiz');

        $this->getJson('/api/v1/campaigns/demo')
            ->assertOk()
            ->assertJsonPath('data.slug', 'demo')
            ->assertJsonPath('data.mechanic', 'quiz')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure(['data' => ['color_hex', 'emoji', 'starts_at', 'ends_at', 'accepting_attempts']]);
    }

    #[Test]
    public function the_leaderboard_reflects_scores_and_the_current_participant_position(): void
    {
        $this->seedCampaign('demo', 'quiz', prizes: 0, promoCodes: 0);
        $session = $this->openWebSession('demo');

        $this->withHeader('Authorization', "Bearer {$session['token']}")
            ->postJson('/api/v1/campaigns/demo/attempts', ['move' => ['answers' => [
                ['question_id' => 'q1', 'option_id' => 'a'],
                ['question_id' => 'q2', 'option_id' => 'b'],
            ]]])
            ->assertOk();

        $board = $this->withHeader('Authorization', "Bearer {$session['token']}")
            ->getJson('/api/v1/campaigns/demo/leaderboard');

        $board->assertOk()
            ->assertJsonPath('data.entries.0.score', 20)
            ->assertJsonPath('data.me.rank', 1);
    }

    #[Test]
    public function my_rewards_lists_a_won_prize_with_its_promo_code(): void
    {
        $this->seedCampaign('demo', 'wheel', prizes: 3, promoCodes: 3);
        $session = $this->openWebSession('demo');
        $auth = ['Authorization' => "Bearer {$session['token']}"];

        $this->withHeaders($auth)->postJson('/api/v1/campaigns/demo/attempts', ['move' => []])->assertOk();

        $this->withHeaders($auth)->getJson('/api/v1/campaigns/demo/rewards/me')
            ->assertOk()
            ->assertJsonPath('data.items.0.title', 'Приз')
            ->assertJsonStructure(['data' => ['items' => [['title', 'promo_code', 'awarded_at']]]]);
    }
}
