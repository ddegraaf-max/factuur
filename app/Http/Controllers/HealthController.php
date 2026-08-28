<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * /health — machineleesbare gezondheidscheck voor externe bewaking (UptimeRobot,
 * de dagelijkse rookproef). Geeft 503 zodra de database niet antwoordt of de
 * planner (schedule:work) meer dan een kwartier geen hartslag heeft gegeven —
 * precies de stille storing die we in augustus 2026 pas na weken ontdekten.
 */
class HealthController extends Controller
{
    public const HEARTBEAT_KEY = 'scheduler.heartbeat';
    public const MAX_HEARTBEAT_AGE_MINUTES = 15;

    public function __invoke()
    {
        $checks = [];

        try {
            DB::select('select 1');
            $checks['database'] = ['ok' => true];
        } catch (\Throwable $e) {
            $checks['database'] = ['ok' => false, 'error' => 'geen verbinding'];
        }

        $beat = (int) Cache::get(self::HEARTBEAT_KEY, 0);
        $age = $beat ? (int) floor((now()->timestamp - $beat) / 60) : null;
        $checks['scheduler'] = [
            'ok' => $beat > 0 && $age <= self::MAX_HEARTBEAT_AGE_MINUTES,
            'last_beat' => $beat ? date('c', $beat) : null,
            'age_minutes' => $age,
        ];

        // Back-up: alleen beoordelen als hij is ingericht; dan mag de laatste
        // geslaagde run niet ouder zijn dan 36 uur (dagelijks om 03:30).
        if (app(\App\Services\BackupService::class)->configured()) {
            $last = (int) Cache::get(\App\Services\BackupService::LAST_OK_KEY, 0);
            $hours = $last ? round((now()->timestamp - $last) / 3600, 1) : null;
            $checks['backup'] = [
                'ok' => $last > 0 && $hours <= 36,
                'last_ok' => $last ? date('c', $last) : null,
                'age_hours' => $hours,
            ];
        }

        $ok = collect($checks)->every(fn ($c) => $c['ok']);

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'version' => config('app.version'),
            'checks' => $checks,
            'time' => now()->toIso8601String(),
        ], $ok ? 200 : 503)->header('Cache-Control', 'no-store');
    }
}
