<?php

namespace App\Console\Commands;

use App\Actions\SyncCallDay;
use App\Models\CallStat;
use App\Services\ZenniaClient;
use Carbon\CarbonPeriod;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('zennia:import-range {from} {to} {--force : Re-fetch days that already have data}')]
#[Description('Importa el histórico de llamadas de Zennia día por día para un rango de fechas.')]
class ZenniaImportRange extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ZenniaClient $client)
    {
        $from = $this->argument('from');
        $to = $this->argument('to');
        $force = (bool) $this->option('force');

        $period = CarbonPeriod::create($from, $to);
        $total = iterator_count($period);

        $this->info("Importando {$total} días de Zennia ({$from} a {$to})...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $imported = 0;
        $skipped = 0;
        $failed = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $dayString = $day->toDateString();

            if (! $force && CallStat::whereDate('date', $dayString)->exists()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                SyncCallDay::run($dayString, $client, source: 'bulk_import');
                $imported++;
            } catch (Throwable $e) {
                $failed[] = "{$dayString}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Importados: {$imported} | Ya existían (omitidos): {$skipped} | Fallidos: ".count($failed));

        if ($failed) {
            $this->warn('Días con error:');
            foreach ($failed as $line) {
                $this->line("  - {$line}");
            }
        }
    }
}
