<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\BillingCycle;
use App\Models\CallLog;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\PaymentReceipt;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeCompanyData extends Command
{
    protected $signature = 'company:purge
                            {company : Company name, UUID, or numeric ID}
                            {--force : Skip confirmation prompt}
                            {--dry-run : Show what would be deleted without deleting anything}';

    protected $description = 'Permanently delete all data for a company (testing/admin use only)';

    public function handle(): int
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

        // ── Count everything ─────────────────────────────────────────────
        $counts = [
            'Payment Receipts'   => PaymentReceipt::whereIn('invoice_id', $invoiceIds)->count(),
            'Payments'           => Payment::whereIn('invoice_id', $invoiceIds)->count(),
            'Payment Links'      => $paymentLinkIds->count(),
            'Invoices'           => $invoiceIds->count(),
            'Billing Cycles'     => BillingCycle::where('company_id', $company->id)->count(),
            'Call Logs'          => CallLog::whereIn('agent_id', $agentIds)->count(),
            'Subscriptions'      => $subscriptionIds->count(),
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

        // ── Delete ───────────────────────────────────────────────────────
        $this->newLine();
        $this->info('Deleting...');

        DB::transaction(function () use ($company, $agentIds, $invoiceIds, $paymentLinkIds, $receiptFiles) {

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

            // 14. Company itself
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
