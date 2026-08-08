<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StatusController extends Controller
{
    public function index()
    {
        // Database: eenvoudige query.
        $dbOk = $this->check(function () {
            DB::select('select 1');

            return true;
        });

        // Opslag (bijlagen staan op de 'local' disk): schrijf + lees + verwijder.
        $storageOk = $this->check(function () {
            $name = 'healthcheck_'.Str::random(10).'.txt';
            Storage::disk('local')->put($name, 'ok');
            $ok = Storage::disk('local')->get($name) === 'ok';
            Storage::disk('local')->delete($name);

            return $ok;
        });

        // Cache: schrijf + lees + verwijder.
        $cacheOk = $this->check(function () {
            $key = 'healthcheck_'.Str::random(10);
            cache()->put($key, '1', 10);
            $ok = cache()->get($key) === '1';
            cache()->forget($key);

            return $ok;
        });

        // E-mail: we versturen niet bij elke paginaweergave — we controleren of
        // er een mail-provider is geconfigureerd (Resend of SMTP).
        $mailOk = ! empty(config('services.resend.key'))
            || ! empty(config('mail.mailers.smtp.host'));

        // PDF-engine aanwezig?
        $pdfOk = $dbOk && (
            class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)
            || class_exists(\Barryvdh\DomPDF\PDF::class)
        );

        $components = [
            ['label' => 'Webapplicatie',            'ok' => true], // rendert = draait
            ['label' => 'Database',                 'ok' => $dbOk],
            ['label' => 'Inloggen & 2FA',           'ok' => $dbOk],
            ['label' => 'Facturen & PDF-generatie', 'ok' => $pdfOk],
            ['label' => 'Opslag (bijlagen)',        'ok' => $storageOk],
            ['label' => 'Cache',                    'ok' => $cacheOk],
            ['label' => 'E-mail versturen',         'ok' => $mailOk],
            ['label' => 'Herinneringen & incasso',  'ok' => $dbOk && $mailOk],
        ];

        $allOk = collect($components)->every(fn ($c) => $c['ok']);

        return view('marketing.status', [
            'components' => $components,
            'allOk' => $allOk,
            'checkedAt' => now(),
        ]);
    }

    private function check(callable $fn): bool
    {
        try {
            return (bool) $fn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
