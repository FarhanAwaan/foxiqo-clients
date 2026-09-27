<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * InvoiceService::voidInvoice() writes status = 'voided', but no migration ever put that
     * value in the enum — production's column has it (added outside the migrations), while a
     * database built from them does not, and voiding an invoice there fails with "Data
     * truncated for column 'status'". This appends it where it's missing and does nothing
     * where it's already present (production, the local dev database).
     */
    public function up(): void
    {
        // Raw MySQL DDL that SQLite (the phpunit driver) can't parse — and SQLite has no
        // enum to extend. Same guard as 2026_09_19_100002.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Extends the column's CURRENT enum instead of restating a list: a hardcoded list that
        // leaves out a value already in use makes MySQL reject the ALTER with "Data truncated".
        // A column that isn't an enum at all has nothing to extend (a varchar takes 'voided'
        // as is), and slicing its type string would produce a broken statement.
        $type = $this->columnType('invoices', 'status');
        if (!str_starts_with($type, 'enum(') || str_contains($type, "'voided'")) {
            return;
        }

        // NOT NULL DEFAULT is restated because MODIFY replaces the whole column definition.
        $type = substr($type, 0, -1) . ",'voided')";
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status {$type} NOT NULL DEFAULT 'draft'");
    }

    /**
     * Deliberately a no-op: 'voided' is live in production and real invoices carry it, so
     * shrinking the enum would either fail or destroy that history — and rolling this
     * migration back on a database that had the value all along must not change it.
     */
    public function down(): void
    {
        //
    }

    private function columnType(string $table, string $column): string
    {
        return DB::selectOne(
            'SELECT COLUMN_TYPE AS col_type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        )->col_type;
    }
};
