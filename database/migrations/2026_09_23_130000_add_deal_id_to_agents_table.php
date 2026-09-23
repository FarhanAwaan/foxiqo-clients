<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Explicit link from the point where it's actually known (admin clicking
        // "Add Assistant" from a specific paid Deal) — a company can have more than
        // one unclaimed deal at once (e.g. two agents bought separately), so
        // matching by company_id alone can't tell which deal funds which agent.
        Schema::table('agents', function (Blueprint $table) {
            $table->foreignId('deal_id')->nullable()->after('company_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deal_id');
        });
    }
};
