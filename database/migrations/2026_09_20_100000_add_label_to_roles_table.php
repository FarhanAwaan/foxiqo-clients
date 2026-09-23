<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A role's `name` is the fixed internal slug that code checks against
     * (isCloser(), role_or_permission:admin|closer, the users.role enum).
     * `label` is what admins see and can rename anytime — e.g. renaming
     * "closer" to "Manager" in the UI never touches the slug or any code.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('label')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
