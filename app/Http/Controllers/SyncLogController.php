<?php

namespace App\Http\Controllers;

use App\Models\SyncLog;

class SyncLogController extends Controller
{
    public function index()
    {
        return response()->json(
            SyncLog::orderByDesc('created_at')->limit(200)->get()
        );
    }
}
