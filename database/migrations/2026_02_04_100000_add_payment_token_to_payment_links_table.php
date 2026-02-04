<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->string('payment_token', 80)->unique()->after('provider_reference');
        });

        // Generate tokens for existing payment links
        DB::table('payment_links')->whereNull('payment_token')->orWhere('payment_token', '')->get()->each(function ($link) {
            DB::table('payment_links')
                ->where('id', $link->id)
                ->update(['payment_token' => Str::random(32) . '-' . bin2hex(random_bytes(8))]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->dropColumn('payment_token');
        });
    }
};
