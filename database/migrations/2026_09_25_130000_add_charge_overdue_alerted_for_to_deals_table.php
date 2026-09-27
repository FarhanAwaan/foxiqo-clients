<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The charge date the "charge overdue" admin alert was already sent for — the same idea as
     * trial_reminded_for. Once Paddle bills, next_billed_at moves on and no longer matches, so the
     * alert re-arms for the next period on its own instead of repeating every reconcile run.
     *
     * Guarded so a re-run after a partial failure finishes instead of aborting on "duplicate column".
     */
    public function up(): void
    {
        if (!Schema::hasColumn('deals', 'charge_overdue_alerted_for')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dateTime('charge_overdue_alerted_for')->nullable()->after('trial_reminded_for');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('deals', 'charge_overdue_alerted_for')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('charge_overdue_alerted_for');
            });
        }
    }
};
