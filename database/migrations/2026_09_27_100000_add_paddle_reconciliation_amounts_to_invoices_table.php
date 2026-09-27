<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `invoices.amount` is always OUR agreed price (e.g. the $497 retainer) — it never changes, and
     * revenue/MRR reporting keeps reading it exactly as before. These four columns are purely for
     * cross-checking against Paddle: what Paddle's own transaction record shows it actually charged
     * the card, so a place where those two numbers should agree (but might not — Paddle adds sales
     * tax/VAT in some jurisdictions) is visible instead of silently assumed. See
     * PaddleLifecycleService / App\Support\PaddleMoney for where they're populated.
     *
     * Guarded so a re-run after a partial failure finishes instead of aborting on "duplicate column".
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // What Paddle's transaction totals.grand_total actually charged the card — the
            // reconciliation anchor. Null for invoices that never went through Paddle (Nsave/manual).
            if (!Schema::hasColumn('invoices', 'paddle_charged_amount')) {
                $table->decimal('paddle_charged_amount', 10, 2)->nullable()->after('paddle_status');
            }
            // The tax portion of paddle_charged_amount, informational — explains a gap from `amount`.
            if (!Schema::hasColumn('invoices', 'paddle_tax_amount')) {
                $table->decimal('paddle_tax_amount', 10, 2)->nullable()->after('paddle_charged_amount');
            }
            // Paddle's platform fee on this transaction — what's actually left after Paddle's cut.
            if (!Schema::hasColumn('invoices', 'paddle_fee_amount')) {
                $table->decimal('paddle_fee_amount', 10, 2)->nullable()->after('paddle_tax_amount');
            }
            // Cumulative amount refunded/charged back on this invoice's transaction (adjustment.*),
            // so a partial refund shows up here instead of just flipping status to 'refunded' and
            // losing how much.
            if (!Schema::hasColumn('invoices', 'paddle_refunded_amount')) {
                $table->decimal('paddle_refunded_amount', 10, 2)->nullable()->after('paddle_fee_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['paddle_charged_amount', 'paddle_tax_amount', 'paddle_fee_amount', 'paddle_refunded_amount'],
                fn ($c) => Schema::hasColumn('invoices', $c)
            ));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
