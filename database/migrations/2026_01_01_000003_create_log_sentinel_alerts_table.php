<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_sentinel_alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entry_id')
                ->nullable()
                ->constrained('log_sentinel_entries')
                ->nullOnDelete();

            $table->foreignId('source_id')
                ->nullable()
                ->constrained('log_sentinel_sources')
                ->nullOnDelete();

            $table->string('severity')->default('medium')->index();
            $table->string('type')->index();

            $table->string('title');
            $table->longText('description')->nullable();

            $table->string('ip_address')->nullable()->index();

            $table->string('fingerprint')->nullable()->index();
            $table->unsignedInteger('occurrence_count')->default(1);

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->string('status')->default('open')->index();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['severity', 'status']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_sentinel_alerts');
    }
};
