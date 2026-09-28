<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Services\PaddleLifecycleService;
use Illuminate\Console\Command;

class ReconcilePaddle extends Command
{
    protected $signature = 'paddle:reconcile';
    protected $description = 'Ask Paddle what happened and apply anything the webhooks missed (paid deals, subscription state, due charges)';

    public function handle(PaddleLifecycleService $lifecycle): int
    {
        // Only the API key matters here — not the "Paddle enabled" toggle. Switching the rail off
        // stops NEW deals; charges on subscriptions already running still have to be recorded.
        if (!SystemSetting::getValue('paddle_api_key')) {
            $this->info('No Paddle API key is configured — nothing to reconcile.');

            return Command::SUCCESS;
        }

        $stats = $lifecycle->reconcile();

        $this->info(sprintf(
            'Paddle reconcile: %d deal(s) newly paid, %d subscription(s) synced, %d missed charge(s) recorded, %d charge(s) overdue, %d failed-delivery alert(s), %d error(s).',
            $stats['deals_paid'],
            $stats['synced'],
            $stats['charges'],
            $stats['overdue'],
            $stats['failed_deliveries'],
            $stats['errors']
        ));

        // Non-zero only when EVERYTHING failed (Paddle unreachable / bad key) — one flaky deal
        // shouldn't page anyone; it's already in the log and retried next run.
        $attempted = $stats['deals_paid'] + $stats['synced'] + $stats['errors'];

        return $stats['errors'] > 0 && $stats['errors'] === $attempted ? Command::FAILURE : Command::SUCCESS;
    }
}
