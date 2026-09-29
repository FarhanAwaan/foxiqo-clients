<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\BillingCycle;
use App\Models\CallLog;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\PaymentReceipt;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaddleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Permanently offboards one real company: cancels any live Paddle subscription(s) first,
 * outside the DB transaction — a failed cancel aborts before anything is deleted, so Paddle
 * never ends up still billing a card with nothing left in the portal to explain the charge.
 * Then deletes the company and everything that references it, including its Deal row(s)
 * (deals.company_id is ON DELETE SET NULL, not CASCADE, so those don't disappear on their own).
 *
 * For repeatable Paddle-sandbox test-account cleanup during development, use
 * `sandbox:reset-customer` instead — it's scoped by email and refuses to run outside sandbox.
 */
class PurgeCompanyData extends Command
{
    protected $signature = 'company:purge
                            {company : Company name, UUID, or numeric ID}
                            {--force : Skip confirmation prompt}
                            {--dry-run : Show what would be deleted without deleting anything}';

    protected $description = 'Permanently offboard a real company: cancel its Paddle subscription(s) and delete all its data (for sandbox test cleanup, use sandbox:reset-customer instead)';

    public function handle(PaddleService $paddle): int
    {
        $identifier = $this->argument('company');

        // ── Resolve company ──────────────────────────────────────────────
        $company = $this->resolveCompany($identifier);

        if (!$company) {
            $this->error("Company not found: \"{$identifier}\"");
            $this->line('Try searching by name, UUID, or numeric ID.');
            return Command::FAILURE;
        }

        // ── Collect IDs we'll need for sub-queries ───────────────────────
        $agentIds        = Agent::where('company_id', $company->id)->pluck('id');
        $subscriptionIds = Subscription::where('company_id', $company->id)->pluck('id');
        $invoiceIds      = Invoice::where('company_id', $company->id)->pluck('id');
        $paymentLinkIds  = PaymentLink::whereIn('invoice_id', $invoiceIds)->pluck('id');

        // Deals don't cascade from the company (company_id is ON DELETE SET NULL), and a deal
        // can still be carrying a live Paddle subscription — fetch full models, not just ids,
        // so we can both delete them explicitly and cancel Paddle before we do.
        $deals        = Deal::where('company_id', $company->id)->get();
        $subsToCancel = $deals->whereNotNull('paddle_subscription_id')->where('paddle_status', '!=', 'canceled');

        // ── Count everything ─────────────────────────────────────────────
        $counts = [
            'Payment Receipts'   => PaymentReceipt::whereIn('invoice_id', $invoiceIds)->count(),
            'Payments'           => Payment::whereIn('invoice_id', $invoiceIds)->count(),
            'Payment Links'      => $paymentLinkIds->count(),
            'Invoices'           => $invoiceIds->count(),
            'Billing Cycles'     => BillingCycle::where('company_id', $company->id)->count(),
            'Call Logs'          => CallLog::whereIn('agent_id', $agentIds)->count(),
            'Subscriptions'      => $subscriptionIds->count(),
            'Deals'              => $deals->count(),
            'Notifications'      => Notification::where('company_id', $company->id)->count(),
            'Audit Logs'         => AuditLog::where('company_id', $company->id)->count(),
            'Agents'             => $agentIds->count(),
            'Users'              => User::where('company_id', $company->id)->count(),
            'Custom Plans'       => Plan::where('company_id', $company->id)->count(),
        ];

        $receiptFiles = PaymentReceipt::whereIn('invoice_id', $invoiceIds)->pluck('file_path');

        // ── Display summary ──────────────────────────────────────────────
        $this->newLine();
        $this->line("<fg=red;options=bold>  ⚠  COMPANY PURGE: {$company->name} (ID: {$company->id})</>");
        $this->newLine();
        $this->table(
            ['Record Type', 'Count'],
            collect($counts)->map(fn ($count, $type) => [$type, $count])->values()->toArray()
        );

        if ($receiptFiles->isNotEmpty()) {
            $this->line("  + {$receiptFiles->count()} file(s) will be deleted from storage");
        }

        if ($subsToCancel->isNotEmpty()) {
            $this->line("  + {$subsToCancel->count()} active Paddle subscription(s) will be CANCELLED before deletion");
        }

        $this->newLine();
        $this->line('<fg=yellow>  This action is IRREVERSIBLE. All data will be permanently deleted.</>');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No data was deleted.');
            return Command::SUCCESS;
        }

        // ── Confirm ──────────────────────────────────────────────────────
        if (!$this->option('force')) {
            $confirmed = $this->ask("Type the company name to confirm deletion");

            if ($confirmed !== $company->name) {
                $this->error('Name did not match. Aborting.');
                return Command::FAILURE;
            }
        }

        // ── Cancel Paddle first, outside the transaction ─────────────────
        // A failed cancel aborts here with everything still intact, rather than deleting the
        // company/deal and leaving Paddle still billing the card with nothing left in the
        // portal to explain it.
        foreach ($subsToCancel as $deal) {
            try {
                $paddle->cancelSubscription($deal->paddle_subscription_id, immediately: true);
                $this->info("Cancelled Paddle subscription {$deal->paddle_subscription_id}.");
            } catch (\Throwable $e) {
                $this->error("Could not cancel Paddle subscription {$deal->paddle_subscription_id}: {$e->getMessage()}");
                $this->error('Aborting — nothing has been deleted. Resolve the Paddle-side issue, then re-run.');

                return Command::FAILURE;
            }
        }

        // ── Delete ───────────────────────────────────────────────────────
        $this->newLine();
        $this->info('Deleting...');

        DB::transaction(function () use ($company, $agentIds, $invoiceIds, $paymentLinkIds, $receiptFiles, $deals) {

            // 1. Delete physical receipt files from storage
            foreach ($receiptFiles as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            // 2. Payment Receipts (FK: payment_link_id → cascadeOnDelete, invoice_id → cascadeOnDelete)
            PaymentReceipt::whereIn('invoice_id', $invoiceIds)->delete();

            // 3. Payments (FK: invoice_id → cascadeOnDelete)
            Payment::whereIn('invoice_id', $invoiceIds)->delete();

            // 4. Payment Links (FK: invoice_id → cascadeOnDelete)
            PaymentLink::whereIn('invoice_id', $invoiceIds)->delete();

            // 5. Invoices (FK: company_id → cascadeOnDelete)
            Invoice::where('company_id', $company->id)->delete();

            // 6. Billing Cycles (FK: company_id → cascadeOnDelete)
            BillingCycle::where('company_id', $company->id)->delete();

            // 7. Call Logs (FK: agent_id → cascadeOnDelete)
            CallLog::whereIn('agent_id', $agentIds)->delete();

            // 8. Subscriptions (FK: company_id → cascadeOnDelete)
            Subscription::where('company_id', $company->id)->delete();

            // 9. Notifications (FK: company_id → cascadeOnDelete)
            Notification::where('company_id', $company->id)->delete();

            // 10. Audit Logs (FK: company_id → nullOnDelete — must delete manually)
            AuditLog::where('company_id', $company->id)->delete();

            // 11. Agents (FK: company_id → cascadeOnDelete)
            Agent::where('company_id', $company->id)->delete();

            // 12. Users (FK: company_id → nullOnDelete — must delete manually)
            User::where('company_id', $company->id)->delete();

            // 13. Custom Plans belonging to this company (FK: company_id → nullOnDelete — must delete manually)
            Plan::where('company_id', $company->id)->delete();

            // 14. Deals (FK: company_id → nullOnDelete — must delete manually; would otherwise
            //     survive the purge orphaned, with company_id nulled out and its paddle_* ids intact)
            Deal::whereIn('id', $deals->pluck('id'))->delete();

            // 15. Company itself
            $company->delete();
        });

        $this->newLine();
        $this->info("✓ Company \"{$company->name}\" and all related data have been permanently deleted.");
        $this->newLine();

        return Command::SUCCESS;
    }

    private function resolveCompany(string $identifier): ?Company
    {
        // Numeric ID
        if (is_numeric($identifier)) {
            return Company::find((int) $identifier);
        }

        // UUID format
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
            return Company::where('uuid', $identifier)->first();
        }

        // Name — exact first, then partial
        return Company::where('name', $identifier)->first()
            ?? Company::where('name', 'like', "%{$identifier}%")->first();
    }
}
