<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SeedsDemoCampaign;
use Tests\TestCase;

final class AuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDemoCampaign;

    #[Test]
    public function playing_an_attempt_without_a_session_token_is_rejected(): void
    {
        $this->seedCampaign();

        $this->postJson('/api/v1/campaigns/demo/attempts', ['move' => []])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function my_rewards_without_a_session_token_is_rejected(): void
    {
        $this->seedCampaign();

        $this->getJson('/api/v1/campaigns/demo/rewards/me')->assertStatus(401);
    }

    #[Test]
    public function admin_endpoints_reject_a_missing_token(): void
    {
        $this->getJson('/api/v1/admin/campaigns')->assertStatus(401);
        $this->postJson('/api/v1/admin/campaigns', [])->assertStatus(401);
    }

    #[Test]
    public function admin_endpoints_reject_a_wrong_token(): void
    {
        $this->withHeader('Authorization', 'Bearer wrong-token')
            ->getJson('/api/v1/admin/campaigns')
            ->assertStatus(401);
    }

    #[Test]
    public function admin_endpoints_accept_the_configured_token(): void
    {
        $this->withHeader('Authorization', 'Bearer test-admin-token')
            ->getJson('/api/v1/admin/campaigns')
            ->assertOk()
            ->assertJsonStructure(['data' => ['items']]);
    }

    #[Test]
    public function a_session_token_from_one_campaign_cannot_play_another(): void
    {
        $this->seedCampaign('demo');
        $this->seedCampaign('other');
        $session = $this->openWebSession('demo');

        $this->withHeader('Authorization', "Bearer {$session['token']}")
            ->postJson('/api/v1/campaigns/other/attempts', ['move' => []])
            ->assertStatus(403);
    }
}
