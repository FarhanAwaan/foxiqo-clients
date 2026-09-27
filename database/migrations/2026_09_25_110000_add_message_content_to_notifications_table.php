<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Until now a notification row kept only a one-line summary; the real message lived in the
     * queue job and vanished once sent, so Admin → Emails could list emails but never show one.
     * These two columns keep what was actually sent (SendEmailJob fills them). Re-runnable.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // {from, to, cc, bcc, reply_to}: each a list of {email, name?}
            if (!Schema::hasColumn('notifications', 'envelope')) {
                $table->json('envelope')->nullable()->after('data');
            }
            // The rendered HTML body exactly as delivered. Never filled for emails that carry a
            // private sign-in link (Notification::UNSTORED_CONTENT_TYPES).
            if (!Schema::hasColumn('notifications', 'html_body')) {
                $table->mediumText('html_body')->nullable()->after('envelope');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $columns = array_values(array_filter(['envelope', 'html_body'], fn ($c) => Schema::hasColumn('notifications', $c)));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
