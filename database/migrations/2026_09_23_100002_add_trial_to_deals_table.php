<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // The $999 activation fee still charges immediately either way — only the
            // recurring monthly line item gets a Paddle trial_period when this is set.
            $table->boolean('is_trial')->default(false)->after('activation_price');
            $table->unsignedInteger('trial_days')->nullable()->after('is_trial');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['is_trial', 'trial_days']);
        });
    }
};
