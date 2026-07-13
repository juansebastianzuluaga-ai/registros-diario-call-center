<?php

namespace Tests\Feature;

use App\Jobs\SyncCallRangeJob;
use App\Models\CallStat;
use App\Models\SyncJob;
use App\Models\User;
use App\Services\ZenniaClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Support\ZenniaFixtures;
use Tests\TestCase;

class CallStatControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A single ZenniaClient instance is reused across every day in a refresh
     * loop, so it only logs in once but fetches the report once per day.
     */
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

    public function test_guests_cannot_access_call_stats(): void
    {
        $this->getJson('/api/call-stats/today')->assertStatus(401);
    }

    public function test_index_returns_call_stats_within_the_requested_range(): void
    {
        Sanctum::actingAs(User::factory()->create());

        CallStat::factory()->create(['date' => '2026-06-01', 'total' => 50]);
        CallStat::factory()->create(['date' => '2026-06-15', 'total' => 80]);
        CallStat::factory()->create(['date' => '2026-05-01', 'total' => 10]);

        $response = $this->getJson('/api/call-stats?from=2026-06-01&to=2026-06-30');

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_refresh_with_a_short_range_runs_synchronously(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->fakeZenniaHttp(days: 3);

        $response = $this->postJson('/api/call-stats/refresh', [
            'date' => '2026-06-01',
            'date_to' => '2026-06-03',
        ]);

        $response->assertOk();
        $response->assertJsonCount(3);
        $this->assertSame(3, CallStat::count());
        $this->assertDatabaseCount('sync_jobs', 0);
    }

    public function test_refresh_with_a_long_range_dispatches_a_background_job(): void
    {
        Queue::fake();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/call-stats/refresh', [
            'date' => '2026-06-01',
            'date_to' => '2026-06-20',
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('sync_job.total', 20);

        $this->assertDatabaseCount('sync_jobs', 1);
        $syncJob = SyncJob::first();
        $this->assertSame('2026-06-01', $syncJob->from_date->toDateString());
        $this->assertSame('2026-06-20', $syncJob->to_date->toDateString());

        Queue::assertPushed(SyncCallRangeJob::class);
    }

    public function test_sync_job_status_can_be_polled(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $syncJob = SyncJob::create([
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-20',
            'status' => 'running',
            'total' => 20,
            'processed' => 12,
        ]);

        $response = $this->getJson("/api/sync-jobs/{$syncJob->id}");

        $response->assertOk();
        $response->assertJsonPath('status', 'running');
        $response->assertJsonPath('processed', 12);
    }
}
