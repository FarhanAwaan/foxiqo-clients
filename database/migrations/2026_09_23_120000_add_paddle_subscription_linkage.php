<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Captured from the transaction.completed payload so a later Subscription
            // can be linked to the same Paddle subscription for renewal reconciliation.
            $table->string('paddle_subscription_id')->nullable()->after('paddle_transaction_id');
            // Set once this deal's terms (price, trial) have been applied to a real
            // Subscription — prevents re-applying the same deal to a second agent.
            $table->foreignId('subscription_id')->nullable()->after('company_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            // Null for subscriptions not originated through Paddle (admin-created,
            // Nsave-billed). When set, Paddle — not this app's own renewal cron — is
            // the source of truth for when this subscription's retainer gets charged.
            $table->string('paddle_subscription_id')->nullable()->unique()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn('paddle_subscription_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('paddle_subscription_id');
        });
    }
};
