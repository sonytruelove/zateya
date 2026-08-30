<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SeedsDemoCampaign;
use Tests\TestCase;

final class AdminCampaignTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDemoCampaign;

    private const AUTH = ['Authorization' => 'Bearer test-admin-token'];

    #[Test]
    public function an_organiser_creates_publishes_and_inspects_a_campaign(): void
    {
        $create = $this->withHeaders(self::AUTH)->postJson('/api/v1/admin/campaigns', [
            'slug' => 'spring-wheel',
            'title' => 'Весеннее колесо',
            'mechanic' => 'wheel',
            'starts_at' => now()->subDay()->toIso8601String(),
            'ends_at' => now()->addMonth()->toIso8601String(),
            'attempts_per_participant' => 5,
            'mechanic_settings' => ['sectors' => [['label' => 'Приз', 'weight' => 1, 'winning' => true, 'points' => 10]]],
        ])->assertCreated();

        $id = $create->json('data.campaign_id');

        $this->withHeaders(self::AUTH)->postJson("/api/v1/admin/campaigns/{$id}/publish")->assertOk();
        $this->withHeaders(self::AUTH)->postJson("/api/v1/admin/campaigns/{$id}/prizes", ['title' => 'Подарок', 'quantity' => 20])
            ->assertCreated()
            ->assertJsonPath('data.prize_pool_left', 20);

        $this->withHeaders(self::AUTH)->getJson("/api/v1/admin/campaigns/{$id}/stats")
            ->assertOk()
            ->assertJsonPath('data.prize_pool_left', 20)
            ->assertJsonPath('data.attempts', 0);
    }

    #[Test]
    public function a_duplicate_slug_is_reported_as_a_conflict(): void
    {
        $this->seedCampaign('taken');

        $this->withHeaders(self::AUTH)->postJson('/api/v1/admin/campaigns', [
            'slug' => 'taken',
            'title' => 'Другая кампания',
            'mechanic' => 'wheel',
            'starts_at' => now()->toIso8601String(),
            'ends_at' => now()->addWeek()->toIso8601String(),
            'mechanic_settings' => ['sectors' => [['label' => 'A', 'weight' => 1, 'winning' => true, 'points' => 1]]],
        ])->assertStatus(409)->assertJsonPath('error.code', 'slug_taken');
    }

    #[Test]
    public function deleting_a_campaign_removes_all_related_rows(): void
    {
        $id = $this->seedCampaign('erase-me', prizes: 4, promoCodes: 4);
        $session = $this->openWebSession('erase-me');
        $this->withHeader('Authorization', "Bearer {$session['token']}")
            ->postJson('/api/v1/campaigns/erase-me/attempts', ['move' => []])->assertOk();

        self::assertSame(1, DB::table('attempts')->where('campaign_id', $id)->count());

        $this->withHeaders(self::AUTH)->deleteJson("/api/v1/admin/campaigns/{$id}")->assertNoContent();

        self::assertSame(0, DB::table('campaigns')->where('id', $id)->count());
        self::assertSame(0, DB::table('attempts')->where('campaign_id', $id)->count());
        self::assertSame(0, DB::table('participants')->where('campaign_id', $id)->count());
        self::assertSame(0, DB::table('prizes')->where('campaign_id', $id)->count());
        self::assertSame(0, DB::table('promo_codes')->where('campaign_id', $id)->count());
        $this->getJson('/api/v1/campaigns/erase-me')->assertStatus(404);
    }

    #[Test]
    public function promo_codes_upload_accepts_a_csv_file_but_rejects_an_executable(): void
    {
        $id = $this->seedCampaign('codes', promoCodes: 0);

        $csv = UploadedFile::fake()->createWithContent('codes.csv', "AAA-1\nAAA-2\nAAA-3\n");
        $this->withHeaders(self::AUTH)->post("/api/v1/admin/campaigns/{$id}/promo-codes", ['file' => $csv])
            ->assertCreated()
            ->assertJsonPath('data.added', 3);

        $php = UploadedFile::fake()->createWithContent('codes.php', '<?php echo 1; ?>');
        $this->withHeaders(self::AUTH)->post("/api/v1/admin/campaigns/{$id}/promo-codes", ['file' => $php])
            ->assertStatus(422);
    }
}
