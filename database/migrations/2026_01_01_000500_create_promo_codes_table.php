<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('campaign_id');
            $table->string('code', 64);
            $table->string('code_upper', 64);
            $table->uuid('issued_to_participant_id')->nullable();
            $table->string('issued_at', 40)->nullable();

            $table->unique(['campaign_id', 'code_upper']);
            $table->index(['campaign_id', 'issued_to_participant_id', 'id']);
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
