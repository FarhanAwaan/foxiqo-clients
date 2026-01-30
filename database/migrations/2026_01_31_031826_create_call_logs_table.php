<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('retell_call_id', 100)->unique();
            $table->enum('call_status', ['started', 'ended', 'analyzed'])->default('started');
            $table->enum('direction', ['inbound', 'outbound'])->nullable();
            $table->string('from_number', 20)->nullable();
            $table->string('to_number', 20)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('duration_minutes', 8, 2)->nullable();
            $table->decimal('retell_cost', 10, 4)->nullable();
            $table->longText('transcript')->nullable();
            $table->text('summary')->nullable();
            $table->string('sentiment', 50)->nullable();
            $table->string('recording_url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index('call_status');
            $table->index('created_at');
            $table->index(['agent_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
