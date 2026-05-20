<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_sentinel_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('source_id')
                ->nullable()
                ->constrained('log_sentinel_sources')
                ->nullOnDelete();

            $table->string('source_type')->nullable()->index();

            $table->string('level')->nullable()->index();
            $table->longText('message');

            $table->json('context')->nullable();

            $table->string('ip_address')->nullable()->index();
            $table->string('method', 20)->nullable();
            $table->text('url')->nullable();
            $table->integer('status_code')->nullable()->index();

            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('exception_class')->nullable()->index();
            $table->text('file')->nullable();
            $table->integer('line')->nullable();

            $table->timestamp('occurred_at')->nullable()->index();

            $table->string('hash')->nullable()->index();

            $table->timestamps();

            $table->index(['level', 'occurred_at']);
            $table->index(['source_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_sentinel_entries');
    }
};
