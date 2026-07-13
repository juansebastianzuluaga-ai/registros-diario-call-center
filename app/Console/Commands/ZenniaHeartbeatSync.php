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

#[Signature('zennia:heartbeat')]
#[Description('Sincroniza solo el día de hoy con Zennia. Pensado para correr cada 5 minutos (Programador de Tareas de Windows).')]
class ZenniaHeartbeatSync extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ZenniaClient $client)
    {
        $date = now()->toDateString();

        try {
            $stat = SyncCallDay::run($date, $client, source: 'heartbeat');
            $this->info("Heartbeat OK: total={$stat->total}");
        } catch (ZenniaLoginException|ZenniaParseException $e) {
            $this->error("Heartbeat error: {$e->getMessage()}");
            Log::warning('zennia:heartbeat failed', ['date' => $date, 'error' => $e->getMessage()]);
            $this->raiseAlertOnce($date, $e->getMessage());
        }
    }

    /**
     * This runs every 5 minutes, so without a dedupe check a Zennia outage
     * would spam a new banner alert every single run.
     */
    private function raiseAlertOnce(string $date, string $message): void
    {
        $alreadyRaised = SystemAlert::where('type', 'sync_error')
            ->whereDate('date', $date)
            ->whereNull('resolved_at')
            ->exists();

        if ($alreadyRaised) {
            return;
        }

        SystemAlert::create([
            'type' => 'sync_error',
            'message' => "No se pudo sincronizar {$date}: {$message}",
            'date' => $date,
        ]);
    }
}
