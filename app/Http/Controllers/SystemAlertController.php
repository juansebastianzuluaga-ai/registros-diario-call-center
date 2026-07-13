<?php

namespace App\Http\Controllers;

use App\Models\SystemAlert;

class SystemAlertController extends Controller
{
    public function index()
    {
        return response()->json(
            SystemAlert::whereNull('resolved_at')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
        );
    }

    public function dismiss(SystemAlert $alert)
    {
        $alert->update(['resolved_at' => now()]);

        return response()->json($alert);
    }
}
