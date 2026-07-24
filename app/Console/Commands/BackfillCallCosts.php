<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use App\Services\RetellService;
use Illuminate\Console\Command;

class BackfillCallCosts extends Command
{
    protected $signature = 'calls:backfill-cost {--dry-run : Show what would change without saving}';
    protected $description = 'Re-fetches retell_cost from the Retell API for existing call logs, correcting the cents-to-dollars conversion bug in the old webhook handler';

    public function handle(RetellService $retellService): int
    {
        $dryRun = $this->option('dry-run');

        $calls = CallLog::whereNotNull('retell_call_id')->get();
        $this->info("Found {$calls->count()} call logs to check.");

        $updated = 0;
        $unchanged = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($calls->count());
        $bar->start();

        foreach ($calls as $call) {
            try {
                $data = $retellService->getCallDetails($call->retell_call_id);
                $combinedCost = $data['call_cost']['combined_cost'] ?? null;

                if ($combinedCost === null) {
                    $unchanged++;
                    $bar->advance();
                    continue;
                }

                $correctedCost = round($combinedCost / 100, 4);

                if ((string) $call->retell_cost !== (string) $correctedCost) {
                    if (!$dryRun) {
                        $call->update(['retell_cost' => $correctedCost]);
                    }
                    $updated++;
                } else {
                    $unchanged++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed for call {$call->retell_call_id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $label = $dryRun ? 'Would update' : 'Updated';
        $this->info("{$label}: {$updated}, Unchanged: {$unchanged}, Failed: {$failed}");

        if ($dryRun) {
            $this->comment('Dry run — no records were saved. Re-run without --dry-run to apply.');
        }

        return Command::SUCCESS;
    }
}
