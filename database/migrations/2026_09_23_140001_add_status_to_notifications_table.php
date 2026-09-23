<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('status', 20)->default('queued')->after('channel');
            $table->text('error')->nullable()->after('status');
        });

        // Backfill existing rows so the new status column reflects what already
        // happened — anything with a sent_at clearly sent, everything else is
        // unknown history, treated as sent since nothing failed loudly before now.
        DB::table('notifications')->update(['status' => 'sent']);
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['status', 'error']);
        });
    }
};
