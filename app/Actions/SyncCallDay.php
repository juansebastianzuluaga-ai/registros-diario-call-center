<?php

namespace App\Actions;

use App\Models\CallStat;
use App\Models\CallStatCampaign;
use App\Models\SyncLog;
use App\Services\ZenniaClient;
use Throwable;

class SyncCallDay
{
    /**
     * Fetch a single day's report from Zennia and upsert the CallStat +
     * CallStatCampaign rows for it. Shared by the manual refresh endpoint,
     * the background range job, the bulk historical import command, the
     * scheduled daily sync, and the 5-minute heartbeat — so every sync
     * attempt from anywhere in the app is logged here in one place.
     *
     * @param  string  $source  manual | heartbeat | scheduled_daily | background_range | bulk_import
     */
    public static function run(string $date, ZenniaClient $client, string $source = 'manual'): CallStat
    {
        try {
            $fields = $client->fetchReport($date);
            $campaigns = $fields['campaigns'];
            unset($fields['campaigns']);

            $stat = CallStat::whereDate('date', $date)->first()
                ?? new CallStat(['date' => $date]);
            $stat->fill([...$fields, 'synced_at' => now()]);
            $stat->save();

            foreach ($campaigns as $campaign) {
                $campaignRow = CallStatCampaign::whereDate('date', $date)
                    ->where('campaign_id', $campaign['campaign_id'])
                    ->first() ?? new CallStatCampaign(['date' => $date, 'campaign_id' => $campaign['campaign_id']]);
                $campaignRow->fill($campaign);
                $campaignRow->save();
            }

            SyncLog::create([
                'source' => $source,
                'status' => 'success',
                'date' => $date,
                'message' => "Total del día: {$stat->total}",
            ]);

            return $stat;
        } catch (Throwable $e) {
            SyncLog::create([
                'source' => $source,
                'status' => 'error',
                'date' => $date,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
