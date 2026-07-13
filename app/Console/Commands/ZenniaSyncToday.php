<?php

namespace App\Console\Commands;

use App\Actions\SyncCallDay;
use App\Exceptions\ZenniaLoginException;
use App\Exceptions\ZenniaParseException;
use App\Models\SystemAlert;
use App\Services\ZenniaClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('zennia:sync-today')]
#[Description('Sincroniza el día de hoy y el de ayer con Zennia. Pensado para correr automáticamente (cron / Programador de Tareas de Windows).')]
class ZenniaSyncToday extends Command
{
    /**
     * Above this % of entrantes abandonadas / entrantes_total, raise a
     * "high_abandono" alert so it surfaces as a banner in the dashboard.
     */
    private const ABANDONO_THRESHOLD = 30;

    /**
     * Execute the console command.
     */
    public function handle(ZenniaClient $client)
    {
        // Both today (running total) and yesterday (to catch its final tally
        // once the day has fully closed) — a single scheduled run keeps both current.
        $dates = [now()->subDay()->toDateString(), now()->toDateString()];

        foreach ($dates as $date) {
            try {
                $stat = SyncCallDay::run($date, $client, source: 'scheduled_daily');
                $this->info("Sincronizado {$date}: total={$stat->total}");
                $this->checkAbandono($date, $stat);
            } catch (ZenniaLoginException|ZenniaParseException $e) {
                $this->error("Error sincronizando {$date}: {$e->getMessage()}");
                Log::warning('zennia:sync-today failed', ['date' => $date, 'error' => $e->getMessage()]);

                SystemAlert::create([
                    'type' => 'sync_error',
                    'message' => "No se pudo sincronizar {$date}: {$e->getMessage()}",
                    'date' => $date,
                ]);
            }
        }
    }

    private function checkAbandono(string $date, $stat): void
    {
        if (! $stat->entrantes_total) {
            return;
        }

        $pct = round(($stat->entrantes_abandonadas / $stat->entrantes_total) * 100);

        if ($pct < self::ABANDONO_THRESHOLD) {
            return;
        }

        $alreadyRaised = SystemAlert::where('type', 'high_abandono')
            ->whereDate('date', $date)
            ->whereNull('resolved_at')
            ->exists();

        if ($alreadyRaised) {
            return;
        }

        SystemAlert::create([
            'type' => 'high_abandono',
            'message' => "Abandono del {$pct}% en llamadas entrantes el {$date} (umbral: ".self::ABANDONO_THRESHOLD.'%).',
            'date' => $date,
        ]);
    }
}
