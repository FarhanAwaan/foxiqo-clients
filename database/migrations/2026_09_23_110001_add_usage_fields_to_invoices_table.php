<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A 'usage' invoice is a separate row from the subscription's flat retainer
        // invoice — billed in arrears once a period closes and its actual minutes are
        // known, and collected on its own (the retainer keeps going through whatever
        // rail already collects it; a variable usage amount can't ride along with
        // Paddle's fixed recurring price).
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_type', 20)->default('subscription')->after('company_id');
            $table->unsignedInteger('usage_minutes')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['invoice_type', 'usage_minutes']);
        });
    }
};
