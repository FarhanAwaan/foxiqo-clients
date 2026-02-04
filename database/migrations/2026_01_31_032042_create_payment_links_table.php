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
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 150); //'payoneer', 'stripe', 'manual', 'internal'
            $table->string('provider_reference')->nullable();
            $table->string('payment_url', 500);
            $table->decimal('amount', 10, 2);
            $table->string('status', 150)->default('created'); //'created', 'sent', 'pending', 'paid', 'expired', 'cancelled'
            $table->timestamp('sent_at')->nullable();
            $table->boolean('sent_manually')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('provider');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
