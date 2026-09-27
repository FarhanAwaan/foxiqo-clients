<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';
    protected $description = 'Mark invoices as overdue if past due date';

    public function handle(): int
    {
        // A demo company is fully excluded from automated billing processing (see
        // PaddleLifecycleService::isDemo()) — its invoices are left exactly as they are.
        $count = Invoice::where('status', 'sent')
            ->where('due_date', '<', now())
            ->where(fn ($q) => $q->whereNull('company_id')->orWhereHas('company', fn ($c) => $c->where('is_demo', false)))
            ->update(['status' => 'overdue']);

        $this->info("Marked {$count} invoices as overdue");

        return Command::SUCCESS;
    }
}
