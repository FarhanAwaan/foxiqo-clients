<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'webhook_signature')) {
                $table->string('webhook_signature', 64)->nullable()->unique()->after('status');
            }
        });

        // Generate webhook signatures for existing companies
        $companies = \App\Models\Company::whereNull('webhook_signature')->get();
        foreach ($companies as $company) {
            $company->update([
                'webhook_signature' => 'whsig_' . Str::random(40),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'webhook_signature')) {
                $table->dropColumn('webhook_signature');
            }
        });
    }
};
