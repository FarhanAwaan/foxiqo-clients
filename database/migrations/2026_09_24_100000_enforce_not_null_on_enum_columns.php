<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Earlier raw MODIFY COLUMN statements restated these columns without NOT NULL,
        // which silently made them nullable (a MODIFY replaces the whole definition).
        // Restores the constraint; types and defaults are unchanged.
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'customer', 'closer') NOT NULL DEFAULT 'customer'");
        DB::statement("ALTER TABLE invoices MODIFY status ENUM('draft','sent','paid','overdue','cancelled','refunded') NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE invoices MODIFY invoice_type VARCHAR(20) NOT NULL DEFAULT 'subscription'");
    }

    public function down(): void
    {
        // Corrective only — the previous (nullable) state was the bug, nothing to restore.
    }
};
