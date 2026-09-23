<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-granted, per-user visibility into a Company — currently only meaningful
        // for the closer/manager role (admin already sees every company; customer is
        // already scoped to their own company_id). Existence of a row is the grant
        // itself; there's no separate enabled flag to drift out of sync with.
        Schema::create('company_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user_access');
    }
};
