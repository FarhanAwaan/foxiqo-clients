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

        Schema::table('invoices', function (Blueprint $table) {
            // Paddle's own transaction status (draft/billed/paid/completed/canceled/
            // past_due), kept separate from our own workflow `status` — this is what
            // "track the actual status from Paddle" means: our status drives our
            // workflow (draft/sent/overdue), this one is Paddle's live truth for
            // transactions that went through Paddle at all (null otherwise).
            $table->string('paddle_status', 30)->nullable()->after('paddle_transaction_id');
        });

        // MySQL enums need a raw ALTER — same pattern as the closer-role enum
        // addition earlier in this app's history.
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','paid','overdue','cancelled','refunded') DEFAULT 'draft'");
        DB::statement("ALTER TABLE invoices MODIFY COLUMN invoice_type VARCHAR(20) DEFAULT 'subscription'");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('paddle_status');
        });

        DB::statement("UPDATE invoices SET status = 'cancelled' WHERE status = 'refunded'");
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft'");

        DB::statement('DELETE FROM invoices WHERE subscription_id IS NULL');
        DB::statement('ALTER TABLE invoices MODIFY COLUMN subscription_id BIGINT UNSIGNED NOT NULL');
    }
};
