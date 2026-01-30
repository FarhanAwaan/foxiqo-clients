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
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('plan_name', 100);
            $table->decimal('subscription_amount', 10, 2);
            $table->unsignedInteger('included_minutes');
            $table->unsignedInteger('minutes_used');
            $table->unsignedInteger('total_calls');
            $table->decimal('retell_cost', 10, 4);
            $table->decimal('profit', 10, 4);
            $table->decimal('profit_margin', 5, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('subscription_id');
            $table->index('agent_id');
            $table->index('company_id');
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_cycles');
    }
};
