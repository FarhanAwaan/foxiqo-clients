<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Written to be re-runnable: MySQL DDL isn't transactional, so if one statement
     * fails the earlier ones stay applied — every column is guarded so a second run
     * finishes the job instead of aborting on "duplicate column".
     *
     * A Deal owns the Paddle subscription from the moment it's paid, well before any
     * Agent/Subscription exists in the portal, so the mirror of Paddle's subscription
     * state (status, trial end, next charge, pending cancellation) lives here.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // 'recurring' = setup fee and/or a monthly retainer (optionally with a
            // trial). 'setup_only' = one-time charge, no Paddle subscription — the
            // retainer is agreed later and becomes a child deal (parent_deal_id).
            // A plain string, not an enum: nothing to restate in a later MODIFY.
            if (!Schema::hasColumn('deals', 'billing_mode')) {
                $table->string('billing_mode', 20)->default('recurring')->after('activation_price');
            }
            if (!Schema::hasColumn('deals', 'parent_deal_id')) {
                $table->foreignId('parent_deal_id')->nullable()->after('subscription_id')
                    ->constrained('deals')->nullOnDelete();
            }

            // Mirror of the Paddle subscription. Written only by PaddleLifecycleService.
            if (!Schema::hasColumn('deals', 'paddle_status')) {
                $table->string('paddle_status', 20)->nullable()->after('paddle_subscription_id');
            }
            // The first real charge date while trialing (null once billing has started).
            if (!Schema::hasColumn('deals', 'trial_ends_at')) {
                $table->dateTime('trial_ends_at')->nullable()->after('paddle_status');
            }
            if (!Schema::hasColumn('deals', 'next_billed_at')) {
                $table->dateTime('next_billed_at')->nullable()->after('trial_ends_at');
            }
            if (!Schema::hasColumn('deals', 'paddle_period_start')) {
                $table->dateTime('paddle_period_start')->nullable()->after('next_billed_at');
            }
            if (!Schema::hasColumn('deals', 'paddle_period_end')) {
                $table->dateTime('paddle_period_end')->nullable()->after('paddle_period_start');
            }
            // A scheduled change Paddle will apply later, e.g. "cancel" at the end of the trial.
            if (!Schema::hasColumn('deals', 'paddle_scheduled_change')) {
                $table->string('paddle_scheduled_change', 20)->nullable()->after('paddle_period_end');
            }
            if (!Schema::hasColumn('deals', 'paddle_scheduled_change_at')) {
                $table->dateTime('paddle_scheduled_change_at')->nullable()->after('paddle_scheduled_change');
            }
            if (!Schema::hasColumn('deals', 'paddle_canceled_at')) {
                $table->dateTime('paddle_canceled_at')->nullable()->after('paddle_scheduled_change_at');
            }

            // The first-charge date the "trial ending" reminder was already sent for —
            // when the date moves (an extension) it no longer matches, so it re-arms.
            if (!Schema::hasColumn('deals', 'trial_reminded_for')) {
                $table->dateTime('trial_reminded_for')->nullable()->after('paddle_canceled_at');
            }
            if (!Schema::hasColumn('deals', 'link_emailed_at')) {
                $table->dateTime('link_emailed_at')->nullable()->after('trial_reminded_for');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Ties the invoices a deal produces before an Agent/Subscription exists
            // (the activation fee, a retainer charge that lands early) back to it, so
            // they can be listed on the deal and adopted by the Subscription later.
            if (!Schema::hasColumn('invoices', 'deal_id')) {
                $table->foreignId('deal_id')->nullable()->after('subscription_id')
                    ->constrained('deals')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'deal_id')) {
                $table->dropConstrainedForeignId('deal_id');
            }
        });

        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'parent_deal_id')) {
                $table->dropConstrainedForeignId('parent_deal_id');
            }

            $columns = array_values(array_filter([
                'billing_mode', 'paddle_status', 'trial_ends_at', 'next_billed_at',
                'paddle_period_start', 'paddle_period_end', 'paddle_scheduled_change',
                'paddle_scheduled_change_at', 'paddle_canceled_at', 'trial_reminded_for',
                'link_emailed_at',
            ], fn ($c) => Schema::hasColumn('deals', $c)));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
