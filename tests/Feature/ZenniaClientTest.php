<?php

namespace Tests\Feature;

use App\Exceptions\ZenniaLoginException;
use App\Exceptions\ZenniaParseException;
use App\Services\ZenniaClient;
use Illuminate\Support\Facades\Http;
use Tests\Support\ZenniaFixtures;
use Tests\TestCase;

class ZenniaClientTest extends TestCase
{
    private function baseUrl(): string
    {
        return rtrim(config('services.zennia.base_url'), '/');
    }

    public function test_fetches_and_parses_a_report(): void
    {
        $base = $this->baseUrl();

        Http::fake([
            "$base/accounts/login/" => Http::sequence()
                ->push(ZenniaFixtures::loginPage())
                ->push(ZenniaFixtures::loginSuccess()),
            "$base/reporte/llamadas/" => Http::sequence()
                ->push(ZenniaFixtures::reportPage())
                ->push(ZenniaFixtures::reportResponse(ZenniaFixtures::sampleEstadisticas())),
        ]);

        $fields = (new ZenniaClient())->fetchReport('2026-06-01');

        $this->assertSame(100, $fields['total']);
        $this->assertSame(80, $fields['entrantes_total']);
        $this->assertSame(70, $fields['entrantes_atendidas']);
        $this->assertSame(8, $fields['entrantes_abandonadas']);
        $this->assertSame(5, $fields['salientes_manuales_conectadas']);
        $this->assertSame(2, $fields['salientes_manuales_no_conectadas']);
        $this->assertSame(3, $fields['salientes_discador_atendidas']);
        $this->assertSame(9, $fields['salientes_preview_conectadas']);

        $this->assertCount(1, $fields['campaigns']);
        $this->assertSame('58', $fields['campaigns'][0]['campaign_id']);
        $this->assertSame('CAC_SB_EPS', $fields['campaigns'][0]['nombre']);
        $this->assertSame(80, $fields['campaigns'][0]['total']);
        $this->assertSame(70, $fields['campaigns'][0]['atendidas']);
    }

    public function test_throws_login_exception_on_bad_credentials(): void
    {
        $base = $this->baseUrl();

        Http::fake([
            "$base/accounts/login/" => Http::sequence()
                ->push(ZenniaFixtures::loginPage())
                // A failed login re-renders the same form (still has the password field).
                ->push(ZenniaFixtures::loginPage()),
        ]);

        $this->expectException(ZenniaLoginException::class);

        (new ZenniaClient())->fetchReport('2026-06-01');
    }

    public function test_throws_parse_exception_when_estadisticas_field_is_missing(): void
    {
        $base = $this->baseUrl();

        Http::fake([
            "$base/accounts/login/" => Http::sequence()
                ->push(ZenniaFixtures::loginPage())
                ->push(ZenniaFixtures::loginSuccess()),
            "$base/reporte/llamadas/" => Http::sequence()
                ->push(ZenniaFixtures::reportPage())
                ->push('<html><body>Zennia cambió su estructura, sin campo estadisticas.</body></html>'),
        ]);

        $this->expectException(ZenniaParseException::class);

        (new ZenniaClient())->fetchReport('2026-06-01');
    }

    public function test_recovers_from_a_session_that_expired_mid_report_by_relogging_in_once(): void
    {
        $base = $this->baseUrl();

        Http::fake([
            "$base/accounts/login/" => Http::sequence()
                ->push(ZenniaFixtures::loginPage())
                ->push(ZenniaFixtures::loginSuccess())
                ->push(ZenniaFixtures::loginPage())
                ->push(ZenniaFixtures::loginSuccess()),
            "$base/reporte/llamadas/" => Http::sequence()
                ->push(ZenniaFixtures::reportPage())
                // Reused session died: Zennia bounces the report POST back to the login form.
                ->push(ZenniaFixtures::loginPage())
                ->push(ZenniaFixtures::reportPage())
                ->push(ZenniaFixtures::reportResponse(ZenniaFixtures::sampleEstadisticas())),
        ]);

        $fields = (new ZenniaClient())->fetchReport('2026-06-01');

        $this->assertSame(100, $fields['total']);
    }
}
