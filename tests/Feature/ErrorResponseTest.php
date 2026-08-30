<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_unknown_campaign_returns_a_clean_not_found_body_without_internals(): void
    {
        $response = $this->getJson('/api/v1/campaigns/no-such-campaign');

        $response->assertStatus(404)->assertJsonPath('error.code', 'not_found');

        $body = $response->getContent();
        self::assertIsString($body);
        self::assertStringNotContainsString('Src\\', $body);
        self::assertStringNotContainsString('vendor', $body);
        self::assertStringNotContainsString('Stack trace', $body);
    }

    #[Test]
    public function validation_errors_are_returned_in_the_unified_shape(): void
    {
        $this->postJson('/api/v1/participation/sessions', ['channel' => 'sms'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    #[Test]
    public function debug_mode_is_disabled_in_the_test_configuration(): void
    {
        self::assertFalse(config('app.debug'));
    }
}
