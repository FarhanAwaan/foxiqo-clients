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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('retell_agent_id', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->decimal('cost_per_minute', 8, 4);
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
