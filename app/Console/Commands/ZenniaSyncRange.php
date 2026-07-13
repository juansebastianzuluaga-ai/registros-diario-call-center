<?php

namespace App\Console\Commands;

use App\Actions\SyncCallDay;
use App\Models\SyncJob;
use App\Services\ZenniaClient;
use Carbon\CarbonPeriod;
use Illuminate\Console\Attributes\Argument;
use Illuminate\Console\Attributes\Option;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('zennia:sync-range {from} {to} {--sync-job-id=}')]
class ZenniaSyncRange extends Command
{
    /**
     * Runs a date-range sync as a single GitHub Actions execution (no queue
     * worker in this architecture — every run is a fresh, isolated process),
     * reusing the exact same day-by-day logic the old SyncCallRangeJob used.
     */
    public function handle(ZenniaClient $client)
    {
        $from = $this->argument('from');
        $to = $this->argument('to');
        $syncJobId = $this->option('sync-job-id');

        $job = $syncJobId ? SyncJob::find($syncJobId) : null;
        $job?->update(['status' => 'running']);

        foreach (CarbonPeriod::create($from, $to) as $day) {
            try {
                $stat = SyncCallDay::run($day->toDateString(), $client, source: 'background_range');
                $this->info("Sincronizado {$day->toDateString()}: total={$stat->total}");
                $job?->increment('processed');
            } catch (Throwable $e) {
                $this->error("Error sincronizando {$day->toDateString()}: {$e->getMessage()}");
                $job?->increment('failed');
                $job?->update(['error' => $e->getMessage()]);
            }
        }

        $job?->update(['status' => $job->failed > 0 && $job->processed === 0 ? 'failed' : 'done']);
    }
}
