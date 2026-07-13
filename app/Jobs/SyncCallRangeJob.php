<?php

namespace App\Jobs;

use App\Actions\SyncCallDay;
use App\Models\SyncJob;
use App\Services\ZenniaClient;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncCallRangeJob implements ShouldQueue
{
    use Queueable;

    /**
     * A range can cover many days, each needing several sequential HTTP
     * round-trips to Zennia — well past the queue worker's 60s default.
     */
    public int $timeout = 1800;

    /**
     * Create a new job instance.
     */
    public function __construct(private int $syncJobId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(ZenniaClient $client): void
    {
        $job = SyncJob::find($this->syncJobId);
        if (! $job) {
            return;
        }

        $job->update(['status' => 'running']);

        foreach (CarbonPeriod::create($job->from_date, $job->to_date) as $day) {
            try {
                SyncCallDay::run($day->toDateString(), $client, source: 'background_range');
                $job->increment('processed');
            } catch (Throwable $e) {
                $job->increment('failed');
                $job->update(['error' => $e->getMessage()]);
            }
        }

        $job->update(['status' => $job->failed > 0 && $job->processed === 0 ? 'failed' : 'done']);
    }

    /**
     * If the job dies outright (crash, timeout, worker restart) it never
     * reaches the status update above — without this the SyncJob row would
     * stay "running" forever and the frontend would poll indefinitely.
     */
    public function failed(Throwable $exception): void
    {
        $job = SyncJob::find($this->syncJobId);
        $job?->update(['status' => 'failed', 'error' => $exception->getMessage()]);
    }
}
