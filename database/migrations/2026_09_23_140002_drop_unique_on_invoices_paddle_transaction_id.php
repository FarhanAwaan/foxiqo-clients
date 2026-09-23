<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No longer valid: one Paddle transaction (activation + first month
        // bundled) now produces two Invoice rows — see
        // InvoiceService::createStandaloneInvoice() — both sharing the same
        // paddle_transaction_id. Kept as a plain index for lookups.
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['paddle_transaction_id']);
            $table->index('paddle_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['paddle_transaction_id']);
            $table->unique('paddle_transaction_id');
        });
    }
};
