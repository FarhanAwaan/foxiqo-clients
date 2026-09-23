<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite has no native enum — the column is a plain string with a CHECK
            // constraint at best, and Laravel's own enum() already just stores text.
            // Nothing to alter.
            return;
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'customer', 'closer') DEFAULT 'customer'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'customer') DEFAULT 'customer'");
    }
};
