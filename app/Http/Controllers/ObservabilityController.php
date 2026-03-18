<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ObservabilityController extends Controller
{
    public function health()
    {
        $dbOk = true;
        try {
            DB::select('SELECT 1');
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        return response()->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'database' => $dbOk ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
        ], $dbOk ? 200 : 503);
    }
}
