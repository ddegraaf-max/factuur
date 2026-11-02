<?php

namespace App\Http\Controllers;

use App\Support\Brand;
use Illuminate\Support\Facades\Cache;
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

        // Planner: de hartslag van schedule:work (zie routes/console.php); ouder dan een kwartier = stil.
        $heartbeat = (int) Cache::get(HealthController::HEARTBEAT_KEY, 0);
        $schedulerOk = $heartbeat > 0 && now()->timestamp - $heartbeat < 15 * 60;

        if (Brand::is('fachora')) {
            // 1.97.5: Stripe echt aanspreken (saldo-opvraag, geen wijziging), hooguit eens per tien minuten.
            $stripeKey = (string) config('services.stripe.secret');
            $stripeOk = $stripeKey !== '' && Cache::remember('status.stripe.' . substr(sha1($stripeKey), 0, 12), 600, function () use ($stripeKey) {
                try {
                    return \Illuminate\Support\Facades\Http::withToken($stripeKey)->timeout(8)->get('https://api.stripe.com/v1/balance')->successful();
                } catch (\Throwable) {
                    return false;
                }
            });
            // 1.97.1: Fachora is een marktplaats, geen facturatieprogramma: eigen onderdelen.
            $components = [
                ['label' => __('Webapplicatie'),                      'ok' => true],
                ['label' => __('Database'),                           'ok' => $dbOk],
                ['label' => __('Aanvragen en bevestigingsmails'),     'ok' => $dbOk && $mailOk],
                ['label' => __('Sms-codes'),                          'ok' => app(\App\Services\SmsService::class)->configured()],
                ['label' => __('Bedrijvenregister (CEIDG)'),          'ok' => filled(config('fachora.ceidg_token'))],
                ['label' => __('AI-hulp bij de omschrijving'),        'ok' => app(\App\Services\Ai\StructuredClaude::class)->enabled()],
                ['label' => __('Bedrijvenpaneel en inloggen'),        'ok' => $dbOk],
                // Zonder Stripe-sleutel is dit een bewuste stand (aanvragen gratis), geen storing.
                ['label' => __('Betalingen voor bedrijven (Stripe)'), 'ok' => $stripeOk, 'pending' => $stripeKey === ''],
                ['label' => __("Foto's bij aanvragen"),              'ok' => $dbOk && $storageOk],
                ['label' => __('E-mail versturen'),                   'ok' => $mailOk],
                ['label' => __('Cache'),                              'ok' => $cacheOk],
                ['label' => __('Planner (zoeken in het register, opschonen, herinneringen)'), 'ok' => $schedulerOk],
            ];
        } else {
            $components = [
                ['label' => __('Webapplicatie'),            'ok' => true], // rendert = draait
                ['label' => __('Database'),                 'ok' => $dbOk],
                ['label' => __('Inloggen & 2FA'),           'ok' => $dbOk],
                ['label' => __('Facturen & PDF-generatie'), 'ok' => $pdfOk],
                ['label' => __('Opslag (bijlagen)'),        'ok' => $storageOk],
                ['label' => __('Cache'),                    'ok' => $cacheOk],
                ['label' => __('E-mail versturen'),         'ok' => $mailOk],
                ['label' => __('Herinneringen & incasso'),  'ok' => $dbOk && $mailOk],
            ];
        }

        $allOk = collect($components)->every(fn ($c) => $c['ok'] || ! empty($c['pending']));

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
