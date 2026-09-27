<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A company flagged purely for demo purposes (a working example shown to a prospect, or given
     * to a closer/manager to explore) is fully excluded from automated billing processing — every
     * cron, the Paddle reconcile sweep, and incoming Paddle webhooks all treat it as if it doesn't
     * exist, so it never generates a renewal/usage/overdue email or a recorded invoice. Admin
     * actions taken deliberately on one of its deals (Start billing now, Cancel, etc.) still work
     * normally — only passive/automated processing skips it. See PaddleLifecycleService::isDemo()
     * and PROJECT.md.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('companies', 'is_demo')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'is_demo')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }
    }
};
