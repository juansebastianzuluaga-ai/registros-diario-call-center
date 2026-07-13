<?php

namespace Tests\Feature;

use App\Jobs\SyncCallRangeJob;
use App\Models\CallStat;
use App\Models\SyncJob;
use App\Services\ZenniaClient;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\ZenniaFixtures;
use Tests\TestCase;

class SyncCallRangeJobTest extends TestCase
{
    use RefreshDatabase;

    private function fakeZenniaHttp(int $days): void
    {
        $base = rtrim(config('services.zennia.base_url'), '/');

        $loginSequence = Http::sequence()
            ->push(ZenniaFixtures::loginPage())
            ->push(ZenniaFixtures::loginSuccess());

        $reportSequence = Http::sequence();
        for ($i = 0; $i < $days; $i++) {
            $reportSequence
                ->push(ZenniaFixtures::reportPage())
                ->push(ZenniaFixtures::reportResponse(ZenniaFixtures::sampleEstadisticas()));
        }

        Http::fake([
            "$base/accounts/login/" => $loginSequence,
            "$base/reporte/llamadas/" => $reportSequence,
        ]);
    }

    public function test_processes_every_day_in_the_range_and_marks_the_job_done(): void
    {
        $this->fakeZenniaHttp(days: 5);

        $syncJob = SyncJob::create([
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-05',
            'total' => 5,
        ]);

        (new SyncCallRangeJob($syncJob->id))->handle(new ZenniaClient());

        $syncJob->refresh();
        $this->assertSame('done', $syncJob->status);
        $this->assertSame(5, $syncJob->processed);
        $this->assertSame(0, $syncJob->failed);
        $this->assertSame(5, CallStat::count());
    }

    public function test_a_single_days_failure_does_not_stop_the_rest_of_the_range(): void
    {
        $base = rtrim(config('services.zennia.base_url'), '/');

        Http::fake([
            "$base/accounts/login/" => Http::sequence()
                ->push(ZenniaFixtures::loginPage())
                ->push(ZenniaFixtures::loginSuccess()),
            "$base/reporte/llamadas/" => Http::sequence()
                // Day 1: succeeds.
                ->push(ZenniaFixtures::reportPage())
                ->push(ZenniaFixtures::reportResponse(ZenniaFixtures::sampleEstadisticas()))
                // Day 2: Zennia's structure changed, parsing fails.
                ->push(ZenniaFixtures::reportPage())
                ->push('<html><body>sin estadisticas</body></html>')
                // Day 3: succeeds again.
                ->push(ZenniaFixtures::reportPage())
                ->push(ZenniaFixtures::reportResponse(ZenniaFixtures::sampleEstadisticas())),
        ]);

        $syncJob = SyncJob::create([
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-03',
            'total' => 3,
        ]);

        (new SyncCallRangeJob($syncJob->id))->handle(new ZenniaClient());

        $syncJob->refresh();
        $this->assertSame('done', $syncJob->status);
        $this->assertSame(2, $syncJob->processed);
        $this->assertSame(1, $syncJob->failed);
        $this->assertSame(2, CallStat::count());
    }

    public function test_failed_hook_marks_the_sync_job_as_failed_so_the_frontend_stops_polling(): void
    {
        $syncJob = SyncJob::create([
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-01',
            'status' => 'running',
            'total' => 1,
        ]);

        (new SyncCallRangeJob($syncJob->id))->failed(new Exception('worker crashed'));

        $syncJob->refresh();
        $this->assertSame('failed', $syncJob->status);
        $this->assertSame('worker crashed', $syncJob->error);
    }
}
