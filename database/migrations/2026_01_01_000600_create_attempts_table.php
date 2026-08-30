<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->uuid('participant_id');
            $table->boolean('won');
            $table->unsignedInteger('score');
            $table->string('detail', 255);
            $table->uuid('prize_id')->nullable();
            $table->string('promo_code', 64)->nullable();
            $table->string('played_at', 40);

            $table->index(['campaign_id', 'participant_id']);
            $table->index(['campaign_id', 'played_at']);
            $table->index(['campaign_id', 'won']);
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
