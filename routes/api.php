<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Presentation\Channel\Telegram\TelegramWebhookController;
use Src\Presentation\Channel\Vk\VkCallbackController;
use Src\Presentation\Http\Api\AdminController;
use Src\Presentation\Http\Api\PublicController;
use Src\Presentation\Http\Middleware\EnsureAdminToken;
use Src\Presentation\Http\Middleware\ResolveOptionalParticipant;
use Src\Presentation\Http\Middleware\ResolveParticipant;

Route::prefix('v1')->group(function (): void {
    Route::post('participation/sessions', [PublicController::class, 'openSession'])
        ->middleware('throttle:30,1');

    Route::middleware([ResolveOptionalParticipant::class, 'throttle:120,1'])->group(function (): void {
        Route::get('campaigns/{slug}', [PublicController::class, 'showCampaign']);
        Route::get('campaigns/{slug}/leaderboard', [PublicController::class, 'leaderboard']);
    });

    Route::middleware([ResolveParticipant::class, 'throttle:60,1'])->group(function (): void {
        Route::post('campaigns/{slug}/attempts', [PublicController::class, 'playAttempt']);
        Route::get('campaigns/{slug}/rewards/me', [PublicController::class, 'myRewards']);
    });

    Route::prefix('admin')->middleware(EnsureAdminToken::class)->group(function (): void {
        Route::get('campaigns', [AdminController::class, 'listCampaigns']);
        Route::post('campaigns', [AdminController::class, 'createCampaign']);
        Route::post('campaigns/{id}/publish', [AdminController::class, 'publishCampaign']);
        Route::post('campaigns/{id}/prizes', [AdminController::class, 'addPrizes']);
        Route::post('campaigns/{id}/promo-codes', [AdminController::class, 'uploadPromoCodes']);
        Route::delete('campaigns/{id}', [AdminController::class, 'deleteCampaign']);
        Route::get('campaigns/{id}/stats', [AdminController::class, 'stats']);
    });

    Route::prefix('channels')->group(function (): void {
        Route::post('telegram/{secret}', [TelegramWebhookController::class, 'handle']);
        Route::post('vk/callback', [VkCallbackController::class, 'handle']);
    });
});
