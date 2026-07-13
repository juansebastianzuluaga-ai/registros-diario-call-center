<?php

namespace Tests\Support;

class ZenniaFixtures
{
    public static function loginPage(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/zennia_login_page.html');
    }

    public static function loginSuccess(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/zennia_login_success.html');
    }

    public static function reportPage(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/zennia_report_page.html');
    }

    public static function reportResponse(array $estadisticas): string
    {
        $template = file_get_contents(__DIR__.'/../Fixtures/zennia_report_response.html');
        $json = htmlspecialchars(json_encode($estadisticas), ENT_QUOTES);

        return str_replace('__ESTADISTICAS_JSON__', $json, $template);
    }

    /**
     * A realistic "estadisticas" payload as Zennia embeds it, trimmed to one
     * campaign per call type so field-mapping assertions stay readable.
     */
    public static function sampleEstadisticas(): array
    {
        return [
            'total_llamadas_procesadas' => 100,
            'llamadas_por_tipo' => [
                '1' => ['total' => 7, 'conectadas' => 5, 'no_conectadas' => 2],
                '2' => ['total' => 4, 'atendidas' => 3, 'no_atendidas' => 1, 'perdidas' => 0],
                '3' => [
                    'total' => 80,
                    'atendidas' => 70,
                    'expiradas' => 2,
                    'abandonadas' => 8,
                    'abandonadas_anuncio' => 0,
                    'transferidas_atendidas' => 0,
                    'transferidas_no_atendidas' => 0,
                ],
                '4' => ['total' => 9, 'conectadas' => 9, 'no_conectadas' => 0],
            ],
            'llamadas_por_campana' => [
                '58' => ['nombre' => 'CAC_SB_EPS', 'tipo' => 'Entrante', 'total' => 80, 'manuales' => 0],
            ],
            'tipos_de_llamada_por_campana' => [
                '3' => [
                    '58' => [
                        'nombre' => 'CAC_SB_EPS',
                        'recibidas' => 80,
                        'recibidas_transferencias' => 0,
                        'atendidas' => 70,
                        'expiradas' => 2,
                        'abandonadas' => 8,
                        'abandonadas_anuncio' => 0,
                        't_abandono' => 120.5,
                        't_espera_conexion' => 45.2,
                        'efectuadas_manuales' => 0,
                        'conectadas_manuales' => 0,
                        'no_conectadas_manuales' => 0,
                        't_espera_conexion_manuales' => 0,
                    ],
                ],
            ],
        ];
    }
}
