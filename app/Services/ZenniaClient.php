<?php

namespace App\Services;

use App\Exceptions\ZenniaLoginException;
use App\Exceptions\ZenniaParseException;
use App\Exceptions\ZenniaSessionExpiredException;
use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ZenniaClient
{
    private CookieJar $jar;

    private string $baseUrl;

    private bool $loggedIn = false;

    /**
     * Zennia's "estadisticas" export payload groups totals under numeric type
     * keys. Confirmed by inspecting real data: 1=Manuales, 2=Discador,
     * 3=Entrantes, 4=Preview. Each maps its own value keys to our columns.
     */
    private const TYPE_MAP = [
        '1' => [
            'salientes_manuales_conectadas' => 'conectadas',
            'salientes_manuales_no_conectadas' => 'no_conectadas',
        ],
        '2' => [
            'salientes_discador_atendidas' => 'atendidas',
            'salientes_discador_no_atendidas' => 'no_atendidas',
            'salientes_discador_perdidas' => 'perdidas',
        ],
        '3' => [
            'entrantes_atendidas' => 'atendidas',
            'entrantes_expiradas' => 'expiradas',
            'entrantes_abandonadas' => 'abandonadas',
            'entrantes_abandonadas_anuncio' => 'abandonadas_anuncio',
            'entrantes_transferidas_atendidas' => 'transferidas_atendidas',
            'entrantes_transferidas_no_atendidas' => 'transferidas_no_atendidas',
        ],
        '4' => [
            'salientes_preview_conectadas' => 'conectadas',
            'salientes_preview_no_conectadas' => 'no_conectadas',
        ],
    ];

    private const CAMPAIGN_DETAIL_FIELDS = [
        'recibidas', 'recibidas_transferencias', 'atendidas', 'expiradas',
        'abandonadas', 'abandonadas_anuncio', 't_abandono', 't_espera_conexion',
        'efectuadas_manuales', 'conectadas_manuales', 'no_conectadas_manuales',
        't_espera_conexion_manuales',
    ];

    public function __construct()
    {
        $this->jar = new CookieJar();
        $this->baseUrl = rtrim(config('services.zennia.base_url'), '/');
    }

    /**
     * Fetch and parse the "llamadas" report for a single date or an inclusive date range.
     *
     * @param  string  $date  Y-m-d
     * @param  string|null  $dateTo  Y-m-d, defaults to $date for a single-day report
     */
    public function fetchReport(string $date, ?string $dateTo = null): array
    {
        if (! $this->loggedIn) {
            $this->login();
            $this->loggedIn = true;
        }

        try {
            return $this->requestReport($date, $dateTo);
        } catch (ZenniaSessionExpiredException) {
            // The reused session died mid-batch (Zennia session timeout). Re-login once and retry this day.
            $this->login();
            $this->loggedIn = true;

            return $this->requestReport($date, $dateTo);
        }
    }

    private function requestReport(string $date, ?string $dateTo): array
    {
        $loginReportUrl = $this->baseUrl.'/reporte/llamadas/';
        $reportPage = $this->httpWithRetry()->get($loginReportUrl);

        if (str_contains($reportPage->body(), 'name="password"')) {
            throw new ZenniaSessionExpiredException();
        }

        $csrf = $this->extractCsrfToken($reportPage->body());

        $desde = Carbon::parse($date)->format('d/m/Y');
        $hasta = Carbon::parse($dateTo ?? $date)->format('d/m/Y');

        $response = $this->httpWithRetry()
            ->withHeaders(['Referer' => $loginReportUrl])
            ->asForm()
            ->post($loginReportUrl, [
                'csrfmiddlewaretoken' => $csrf,
                'fecha' => "$desde - $hasta",
            ]);

        if (! $response->successful()) {
            throw new ZenniaParseException("Zennia respondió {$response->status()} al pedir el reporte de llamadas.");
        }

        if (str_contains($response->body(), 'name="password"')) {
            throw new ZenniaSessionExpiredException();
        }

        return $this->parseReportHtml($response->body());
    }

    /**
     * A short, wide retry policy: bulk historical imports run for many minutes
     * and occasionally hit a transient connection timeout against Zennia —
     * retrying a couple of times here is cheaper than failing the whole day.
     */
    private function httpWithRetry()
    {
        return Http::withOptions(['cookies' => $this->jar])
            ->timeout(30)
            ->retry(3, 2000, throw: true);
    }

    private function login(): void
    {
        $loginUrl = $this->baseUrl.'/accounts/login/';

        $loginPage = $this->httpWithRetry()->get($loginUrl);
        $csrf = $this->extractCsrfToken($loginPage->body());

        $response = $this->httpWithRetry()
            ->withHeaders(['Referer' => $loginUrl])
            ->asForm()
            ->post($loginUrl, [
                'csrfmiddlewaretoken' => $csrf,
                'username' => config('services.zennia.username'),
                'password' => config('services.zennia.password'),
                'next' => '',
            ]);

        // A failed login re-renders the same form with a password field; a
        // successful one redirects to the dashboard, which has none.
        if (! $response->successful() || str_contains($response->body(), 'name="password"')) {
            throw new ZenniaLoginException('No se pudo iniciar sesión en Zennia. Verifica ZENNIA_USERNAME/ZENNIA_PASSWORD.');
        }
    }

    private function extractCsrfToken(string $html): string
    {
        $crawler = new Crawler($html);
        $inputs = $crawler->filter('input[name=csrfmiddlewaretoken]');

        if ($inputs->count() === 0) {
            throw new ZenniaParseException('No se encontró el token CSRF en la página de Zennia; puede que la plataforma haya cambiado su estructura.');
        }

        return $inputs->last()->attr('value');
    }

    /**
     * Zennia embeds a hidden "estadisticas" input (used by its own "exportar
     * reporte" form) containing the full report as clean JSON — totals per
     * call type and per campaign. This is far more robust than scraping the
     * on-screen HTML tables, so we parse that instead.
     */
    private function parseReportHtml(string $html): array
    {
        $crawler = new Crawler($html);
        $input = $crawler->filter('input[name=estadisticas]');

        if ($input->count() === 0) {
            throw new ZenniaParseException('No se encontró el campo "estadisticas" en el reporte de Zennia; la plataforma pudo haber cambiado su estructura.');
        }

        $estadisticas = json_decode($input->first()->attr('value'), true);

        if (! is_array($estadisticas) || ! isset($estadisticas['llamadas_por_tipo'])) {
            throw new ZenniaParseException('El campo "estadisticas" de Zennia no tiene el formato esperado.');
        }

        $porTipo = $estadisticas['llamadas_por_tipo'];

        $fields = [];
        foreach (self::TYPE_MAP as $typeKey => $columnToJsonKey) {
            $typeData = $porTipo[$typeKey] ?? [];
            foreach ($columnToJsonKey as $column => $jsonKey) {
                $fields[$column] = (int) ($typeData[$jsonKey] ?? 0);
            }
        }

        $fields['entrantes_total'] = (int) ($porTipo['3']['total'] ?? 0);
        $fields['total'] = (int) ($estadisticas['total_llamadas_procesadas'] ?? 0);
        $fields['raw_payload'] = $estadisticas;
        $fields['campaigns'] = $this->parseCampaigns($estadisticas);

        return $fields;
    }

    private function parseCampaigns(array $estadisticas): array
    {
        $porCampana = $estadisticas['llamadas_por_campana'] ?? [];
        $detallePorTipo = $estadisticas['tipos_de_llamada_por_campana'] ?? [];

        $campaigns = [];
        foreach ($porCampana as $campaignId => $data) {
            $detail = [];
            foreach ($detallePorTipo as $typeKey => $campaignsForType) {
                if (isset($campaignsForType[$campaignId])) {
                    $detail = $campaignsForType[$campaignId];
                    break;
                }
            }

            $row = [
                'campaign_id' => (string) $campaignId,
                'nombre' => $data['nombre'] ?? "campaña $campaignId",
                'tipo' => $data['tipo'] ?? null,
                'total' => (int) ($data['total'] ?? 0),
                'manuales' => (int) ($data['manuales'] ?? 0),
            ];

            foreach (self::CAMPAIGN_DETAIL_FIELDS as $field) {
                $row[$field] = $detail[$field] ?? 0;
            }

            $campaigns[] = $row;
        }

        return $campaigns;
    }
}
