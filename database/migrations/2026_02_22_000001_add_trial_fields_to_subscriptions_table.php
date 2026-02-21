<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('status');
            $table->unsignedSmallInteger('trial_days')->nullable()->after('is_trial');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_days');
            $table->boolean('trial_ending_warned')->default(false)->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_trial', 'trial_days', 'trial_ends_at', 'trial_ending_warned']);
        });
    }
};
