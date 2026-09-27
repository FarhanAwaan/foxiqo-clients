<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set on a RETAINER invoice the moment its usage-overage sibling's charge is sent to Paddle,
     * pointing at that usage invoice — the marker PaddleLifecycleService::resolveRetainerReceipt()
     * checks-and-clears (under a row lock) to decide "send the combined receipt" vs "the usage
     * charge hasn't resolved yet, send the retainer's own receipt alone". See
     * ResolvePendingRetainerReceipt (the timeout fallback) and PaddleLifecycleService::
     * applyUsageCharge() / applyUsageChargeFailure() (the two ways it resolves early).
     *
     * Guarded so a re-run after a partial failure finishes instead of aborting on "duplicate column".
     */
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'usage_receipt_pending_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('usage_receipt_pending_invoice_id')->nullable()->after('paddle_refunded_amount')
                    ->constrained('invoices')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'usage_receipt_pending_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('usage_receipt_pending_invoice_id');
            });
        }
    }
};
