<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('closer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            // Old onboard.php Step 1 — pre-payment prospect/business info
            $table->string('customer_name');
            $table->string('business_name');
            $table->string('industry')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email');
            $table->string('website')->nullable();

            // Agreed on the call
            $table->decimal('agreed_monthly_price', 10, 2);
            $table->decimal('activation_price', 10, 2)->default(999.00);

            $table->enum('status', ['sent', 'paid', 'expired'])->default('sent');
            $table->string('paddle_transaction_id')->nullable()->unique();

            $table->timestamps();

            $table->index('closer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
