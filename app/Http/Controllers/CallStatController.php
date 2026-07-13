<?php

namespace App\Http\Controllers;

use App\Actions\SyncCallDay;
use App\Exceptions\ZenniaLoginException;
use App\Exceptions\ZenniaParseException;
use App\Jobs\SyncCallRangeJob;
use App\Models\CallStat;
use App\Models\CallStatCampaign;
use App\Models\SyncJob;
use App\Services\ZenniaClient;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class CallStatController extends Controller
{
    /**
     * Fixed campaign display order, as always reported by CAC Santa Bárbara's
     * own monthly spreadsheet. Any campaign outside this list (e.g. a newly
     * added one in Zennia) is appended after these, not dropped.
     */
    private const CAMPAIGN_ORDER = [
        'FOMAG', 'Talento_humano_CAC', 'Imagenes_Diagnosticas', 'CAC_SANTA_BARBARA',
        'Programacion_de_cirugia', 'CAC_SB_EPS', 'CAC_SB_PAC', 'Referencia_Contrareferencia',
        'CAC_SB_ARL', 'CAC_SB_MEDICINA_PREPAGADA', 'CAC_SB_OTROS', 'CAC_SB_Especialistas',
        'CAC_SB_PARTICULARES',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json(
            CallStat::whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->orderBy('date')
                ->get()
        );
    }

    public function today()
    {
        return response()->json(
            CallStat::whereDate('date', now()->toDateString())->first()
        );
    }

    public function campaigns(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        return response()->json(
            CallStatCampaign::whereDate('date', $request->input('date'))
                ->orderByDesc('total')
                ->get()
        );
    }

    public function campaignsRange(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $rows = CallStatCampaign::whereDate('date', '>=', $request->input('from'))
            ->whereDate('date', '<=', $request->input('to'))
            ->selectRaw('campaign_id, nombre, SUM(total) as total, SUM(manuales) as manuales, SUM(atendidas) as atendidas, SUM(abandonadas) as abandonadas')
            ->groupBy('campaign_id', 'nombre')
            ->get();

        $order = array_flip(self::CAMPAIGN_ORDER);

        return response()->json(
            $rows->sortBy(fn ($row) => $order[$row->nombre] ?? PHP_INT_MAX)->values()
        );
    }

    /**
     * Ranges longer than this run as a background queued job instead of
     * synchronously in the request — otherwise a big "Actualizar" click from
     * the browser risks hitting PHP's execution time limit.
     */
    private const ASYNC_THRESHOLD_DAYS = 5;

    public function refresh(Request $request, ZenniaClient $client)
    {
        $request->validate([
            'date' => 'required|date',
            'date_to' => 'nullable|date|after_or_equal:date',
        ]);

        $date = $request->input('date');
        $dateTo = $request->input('date_to', $date);
        $dayCount = iterator_count(CarbonPeriod::create($date, $dateTo));

        if ($dayCount > self::ASYNC_THRESHOLD_DAYS) {
            $syncJob = SyncJob::create([
                'from_date' => $date,
                'to_date' => $dateTo,
                'total' => $dayCount,
            ]);

            SyncCallRangeJob::dispatch($syncJob->id);

            return response()->json(['sync_job' => $syncJob], 202);
        }

        try {
            $updated = [];
            foreach (CarbonPeriod::create($date, $dateTo) as $day) {
                $updated[] = SyncCallDay::run($day->toDateString(), $client, source: 'manual');
            }
        } catch (ZenniaLoginException|ZenniaParseException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($updated);
    }
}
