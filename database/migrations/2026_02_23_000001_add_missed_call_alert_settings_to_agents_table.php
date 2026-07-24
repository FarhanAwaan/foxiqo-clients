<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->boolean('missed_call_email_alerts_enabled')->default(false)->after('status');
            $table->string('missed_call_notification_email')->nullable()->after('missed_call_email_alerts_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['missed_call_email_alerts_enabled', 'missed_call_notification_email']);
        });
    }
};
