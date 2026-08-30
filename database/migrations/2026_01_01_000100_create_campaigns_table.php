<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug', 64)->unique();
            $table->string('title', 140);
            $table->string('mechanic', 16);
            $table->string('status', 16);
            $table->string('starts_at', 40);
            $table->string('ends_at', 40);
            $table->string('color_hex', 7);
            $table->string('emoji', 16);
            $table->unsignedSmallInteger('attempts_per_participant');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
