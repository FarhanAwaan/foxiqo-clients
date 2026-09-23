<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Earlier raw MODIFY COLUMN statements restated these columns without NOT NULL,
        // which silently made them nullable (a MODIFY replaces the whole definition).
        // Restores the constraint; each column keeps exactly the type it has now.
        $this->restateNotNull('users', 'role', 'customer');
        $this->restateNotNull('invoices', 'status', 'draft');
        $this->restateNotNull('invoices', 'invoice_type', 'subscription');
    }

    public function down(): void
    {
        // Corrective only — the previous (nullable) state was the bug, nothing to restore.
    }

    private function restateNotNull(string $table, string $column, string $default): void
    {
        // Reuses the column's own current type (enum values included) rather than a
        // hardcoded list, so nothing a real database has picked up — like 'voided' on
        // invoices.status — can be dropped.
        $type = DB::selectOne(
            'SELECT COLUMN_TYPE AS col_type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        )->col_type;

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$type} NOT NULL DEFAULT '{$default}'");
    }
};
