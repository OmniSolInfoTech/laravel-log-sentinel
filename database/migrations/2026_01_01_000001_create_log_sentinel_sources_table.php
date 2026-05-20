<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_sentinel_sources', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('type')->index();
            $table->string('parser')->nullable();

            $table->text('path');
            $table->boolean('enabled')->default(true)->index();

            $table->unsignedBigInteger('last_position')->default(0);
            $table->string('last_inode')->nullable();

            $table->timestamp('last_scanned_at')->nullable();
            $table->integer('scan_interval_minutes')->default(5);
            $table->integer('retention_days')->default(30);

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['type', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_sentinel_sources');
    }
};
