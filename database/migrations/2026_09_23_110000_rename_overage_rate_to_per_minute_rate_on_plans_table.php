<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pricing is now retainer + metered usage from minute 1 (no included-minutes
        // allowance), so "overage" no longer describes what this field charges for.
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('overage_rate', 'per_minute_rate');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('per_minute_rate', 'overage_rate');
        });
    }
};
