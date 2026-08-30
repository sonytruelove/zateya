<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SeedsDemoCampaign;
use Tests\TestCase;

final class ChannelWebhookTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDemoCampaign;

    #[Test]
    public function the_telegram_webhook_rejects_a_wrong_secret(): void
    {
        $this->postJson('/api/v1/channels/telegram/wrong-secret', ['message' => ['from' => ['id' => 1], 'text' => '/play']])
            ->assertStatus(401);
    }

    #[Test]
    public function the_telegram_webhook_plays_an_attempt_on_the_play_command(): void
    {
        $this->seedCampaign('demo', 'wheel');

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-telegram-secret')
            ->postJson('/api/v1/channels/telegram/test-telegram-secret', [
                'message' => ['from' => ['id' => 777, 'first_name' => 'Тимур'], 'text' => '/play'],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    #[Test]
    public function the_vk_callback_answers_the_confirmation_challenge(): void
    {
        $this->postJson('/api/v1/channels/vk/callback', ['type' => 'confirmation', 'group_id' => 1])
            ->assertOk()
            ->assertJsonPath('confirmation', 'test-vk-confirm');
    }

    #[Test]
    public function the_vk_callback_rejects_an_attempt_without_the_shared_secret(): void
    {
        $this->postJson('/api/v1/channels/vk/callback', ['type' => 'attempt', 'user_id' => 5])
            ->assertStatus(401);
    }

    #[Test]
    public function the_vk_callback_plays_an_attempt_with_the_shared_secret(): void
    {
        $this->seedCampaign('demo', 'wheel');

        $this->postJson('/api/v1/channels/vk/callback', [
            'type' => 'attempt',
            'secret' => 'test-vk-secret',
            'user_id' => 909,
        ])->assertOk()->assertJsonStructure(['response' => ['won', 'attempts_left']]);
    }
}
