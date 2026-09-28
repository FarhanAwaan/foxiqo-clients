<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Deal;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\PaddleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Wipes one test customer's footprint so a Paddle sandbox checkout can be re-run with the
 * same email: the Company (which cascades agents/subscriptions/invoices/payments/
 * notifications at the DB level — see the FK ON DELETE rules), plus Deal, the customer User
 * and audit/webhook rows, none of which cascade with the company (Deal and User are
 * ON DELETE SET NULL, webhook_logs has no FK at all).
 *
 * Refuses to run against anything but Paddle sandbox, and refuses this portal's own three
 * standing verification accounts — see feedback_verification_cleanup in project memory.
 */
class SandboxResetCustomer extends Command
{
    protected $signature = 'sandbox:reset-customer {email} {--force : Actually delete instead of previewing}';
    protected $description = 'Sandbox-only: delete one test customer (company, deal(s), invoices, payments, user) by email so a Paddle checkout test can be re-run';

    protected const PROTECTED_EMAILS = [
        'farhan@foxiqo.com',
        'farhanawaan@gmail.com',
        'manager@foxiqo.com',
    ];

    public function handle(PaddleService $paddle): int
    {
        // Bypass SystemSetting::getValue()'s 1h cache on purpose: a stale "sandbox" read here
        // would be the one way this command could ever run against a real customer.
        $environment = DB::table('system_settings')->where('key', 'paddle_environment')->value('value');

        if ($environment !== 'sandbox') {
            $this->error("Refusing to run: paddle_environment is \"{$environment}\", not \"sandbox\". This command never touches live Paddle.");

            return Command::FAILURE;
        }

        $email = strtolower(trim((string) $this->argument('email')));

        if (in_array($email, self::PROTECTED_EMAILS, true)) {
            $this->error("Refusing to touch {$email} — it's one of the portal's own standing verification accounts.");

            return Command::FAILURE;
        }

        $deals = Deal::where('email', $email)->get();
        $company = Company::where('email', $email)->first();
        $user = User::where('email', $email)->where('role', 'customer')->first();

        if ($deals->isEmpty() && !$company && !$user) {
            $this->info("Nothing found for {$email} — already clean.");

            return Command::SUCCESS;
        }

        $webhookLogIds = $this->matchingWebhookLogIds($deals, $company);
        $auditLogIds = $this->matchingAuditLogIds($deals, $company, $user);
        $subsToCancel = $deals->whereNotNull('paddle_subscription_id')->where('paddle_status', '!=', 'canceled');

        $this->table(['Will remove', 'Count'], [
            ['Company', $company ? 1 : 0],
            ['Deal(s)', $deals->count()],
            ['Customer user', $user ? 1 : 0],
            ['Invoices (cascades with company)', $company?->invoices()->count() ?? 0],
            ['Notifications (cascades with company)', $company?->notifications()->count() ?? 0],
            ['Agents/Subscriptions (cascade with company)', $company?->agents()->count() ?? 0],
            ['webhook_logs rows (matched by txn/sub/customer id)', count($webhookLogIds)],
            ['audit_logs rows', count($auditLogIds)],
            ['Paddle subscriptions to cancel', $subsToCancel->count()],
        ]);

        if (!$this->option('force')) {
            $this->comment('Preview only — re-run with --force to actually delete.');

            return Command::SUCCESS;
        }

        // Paddle first, outside the DB transaction: if this fails we bail with the portal
        // record still intact, rather than deleting it and leaving Paddle still billing the card.
        foreach ($subsToCancel as $deal) {
            try {
                $paddle->cancelSubscription($deal->paddle_subscription_id, immediately: true);
                $this->info("Cancelled Paddle subscription {$deal->paddle_subscription_id}.");
            } catch (\Throwable $e) {
                $this->warn("Could not cancel {$deal->paddle_subscription_id} in Paddle (continuing anyway): {$e->getMessage()}");
            }
        }

        DB::transaction(function () use ($deals, $company, $user, $webhookLogIds, $auditLogIds) {
            AuditLog::whereIn('id', $auditLogIds)->delete();
            WebhookLog::whereIn('id', $webhookLogIds)->delete();

            Deal::whereIn('id', $deals->pluck('id'))->delete();
            $user?->delete();

            // Cascades: agents, subscriptions, billing_cycles, invoices, payments,
            // payment_links, payment_receipts, notifications, appointments, company_user_access.
            $company?->delete();
        });

        $this->info("Done — {$email} is clean for a re-test. Paddle keeps its own transaction/customer history regardless (it has no delete API); that's normal and doesn't block a new checkout.");

        return Command::SUCCESS;
    }

    /** @param \Illuminate\Support\Collection<int, Deal> $deals */
    private function matchingWebhookLogIds($deals, ?Company $company): array
    {
        $needles = $deals->pluck('paddle_transaction_id')
            ->merge($deals->pluck('paddle_subscription_id'))
            ->push($company?->paddle_customer_id)
            ->filter()
            ->unique();

        if ($needles->isEmpty()) {
            return [];
        }

        return WebhookLog::where(function ($q) use ($needles) {
            foreach ($needles as $needle) {
                $q->orWhere('payload', 'like', "%{$needle}%");
            }
        })->pluck('id')->all();
    }

    /** @param \Illuminate\Support\Collection<int, Deal> $deals */
    private function matchingAuditLogIds($deals, ?Company $company, ?User $user): array
    {
        return AuditLog::where(function ($q) use ($deals, $company, $user) {
            $q->whereRaw('1 = 0'); // orWhere-only chain needs a false base clause to start from.

            if ($company) {
                $q->orWhere('company_id', $company->id);
                $q->orWhere(fn ($q2) => $q2->where('entity_type', Company::class)->where('entity_id', $company->id));
            }

            foreach ($deals as $deal) {
                $q->orWhere(fn ($q2) => $q2->where('entity_type', Deal::class)->where('entity_id', $deal->id));
            }

            if ($user) {
                $q->orWhere(fn ($q2) => $q2->where('entity_type', User::class)->where('entity_id', $user->id));
            }
        })->pluck('id')->all();
    }
}
