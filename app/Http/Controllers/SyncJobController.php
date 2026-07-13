<?php

namespace App\Http\Controllers;

use App\Models\SyncJob;

class SyncJobController extends Controller
{
    public function show(SyncJob $syncJob)
    {
        return response()->json($syncJob);
    }
}
