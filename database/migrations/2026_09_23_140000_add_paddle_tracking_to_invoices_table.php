<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable so the one-time activation fee can be recorded as its own
        // Invoice the moment Paddle confirms it — before any Agent/Subscription
        // exists to attach it to (see PaddleWebhookController::handleTransactionCompleted()).
        // Raw ALTER (not ->change()) so the existing FK constraint on this column
        // is left untouched — only its nullability changes.
        DB::statement('ALTER TABLE invoices MODIFY COLUMN subscription_id BIGINT UNSIGNED NULL');

        // Guarded because MySQL DDL isn't transactional: if a later statement in
        // this migration fails, this column is already there when it's re-run.
        if (!Schema::hasColumn('invoices', 'paddle_status')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Paddle's own transaction status (draft/billed/paid/completed/canceled/
                // past_due), kept separate from our own workflow `status` — this is what
                // "track the actual status from Paddle" means: our status drives our
                // workflow (draft/sent/overdue), this one is Paddle's live truth for
                // transactions that went through Paddle at all (null otherwise).
                $table->string('paddle_status', 30)->nullable()->after('paddle_transaction_id');
            });
        }

        // Extends the column's CURRENT enum instead of restating a list: production's
        // already holds 'voided' (InvoiceService::voidInvoice), and a hardcoded list that
        // left it out made MySQL reject the ALTER with "Data truncated". NOT NULL is
        // restated because MODIFY replaces the whole column definition.
        $type = $this->columnType('invoices', 'status');
        if (!str_contains($type, "'refunded'")) {
            $type = substr($type, 0, -1) . ",'refunded')";
        }
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status {$type} NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE invoices MODIFY COLUMN invoice_type VARCHAR(20) NOT NULL DEFAULT 'subscription'");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('paddle_status');
        });

        DB::statement("UPDATE invoices SET status = 'cancelled' WHERE status = 'refunded'");
        $type = str_replace(",'refunded'", '', $this->columnType('invoices', 'status'));
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status {$type} NOT NULL DEFAULT 'draft'");

        DB::statement('DELETE FROM invoices WHERE subscription_id IS NULL');
        DB::statement('ALTER TABLE invoices MODIFY COLUMN subscription_id BIGINT UNSIGNED NOT NULL');
    }

    private function columnType(string $table, string $column): string
    {
        return DB::selectOne(
            'SELECT COLUMN_TYPE AS col_type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        )->col_type;
    }
};
