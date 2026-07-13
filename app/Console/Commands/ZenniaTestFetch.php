<?php

namespace App\Console\Commands;

use App\Services\ZenniaClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('zennia:test-fetch {date} {dateTo?}')]
#[Description('Prueba el scraping del reporte de llamadas de Zennia para una fecha (o rango) real, sin tocar la base de datos.')]
class ZenniaTestFetch extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ZenniaClient $client)
    {
        $date = $this->argument('date');
        $dateTo = $this->argument('dateTo');

        $this->info("Consultando Zennia para {$date}".($dateTo ? " a {$dateTo}" : '')."...");

        $data = $client->fetchReport($date, $dateTo);

        $campaigns = $data['campaigns'];
        unset($data['campaigns'], $data['raw_payload']);

        $this->table(['Campo', 'Valor'], collect($data)->map(fn ($v, $k) => [$k, $v])->values());

        $this->newLine();
        $this->line('Por campaña:');
        $this->table(
            ['ID', 'Nombre', 'Tipo', 'Total', 'Manuales', 'Atendidas', 'Abandonadas', 'T. espera (s)'],
            collect($campaigns)->map(fn ($c) => [
                $c['campaign_id'], $c['nombre'], $c['tipo'], $c['total'], $c['manuales'],
                $c['atendidas'], $c['abandonadas'], round($c['t_espera_conexion'], 1),
            ])
        );
    }
}
