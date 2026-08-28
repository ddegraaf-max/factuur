<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dagelijkse herinnering voor bedrijven waarvan de proefperiode bijna eindigt.
Schedule::command('trials:remind')
    ->dailyAt('09:00')
    ->timezone('Europe/Amsterdam');

// Dagelijks: verstuur betalingsherinneringen en aanmaningen voor achterstallige facturen.
Schedule::command('invoices:remind')
    ->dailyAt('08:00')
    ->timezone('Europe/Amsterdam');

// Dagelijks: genereer facturen uit terugkerende profielen die aan de beurt zijn.
Schedule::command('invoices:generate-recurring')
    ->dailyAt('07:00')
    ->timezone('Europe/Amsterdam');

// Dagelijks: boek vaste lasten in uit terugkerende-inkoopprofielen.
Schedule::command('purchases:generate-recurring')
    ->dailyAt('07:10')
    ->timezone('Europe/Amsterdam');

// Dagelijks: verstuur concept-facturen die voor vandaag zijn ingepland.
Schedule::command('invoices:send-scheduled')
    ->dailyAt('07:30')
    ->timezone('Europe/Amsterdam');

// Dagelijks: herinnering aan de btw-aangifte (14 en 3 dagen voor de deadline).
Schedule::command('vat:remind')
    ->dailyAt('08:15')
    ->timezone('Europe/Amsterdam');

// Elke ochtend: het dagoverzicht voor bedrijven die dat aan hebben staan.
Schedule::command('summaries:send')
    ->dailyAt('08:30')
    ->timezone('Europe/Amsterdam');

// Elke vijf minuten: herken nieuwe Postvak IN-items automatisch (scan & herken),
// zodat er een kant-en-klaar boekingsvoorstel klaarligt.
Schedule::command('purchases:scan-inbox')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Maandelijks (de 1e, 07:30): merkgebruik-dossier van de vorige maand naar de eigenaar.
Schedule::command('brand:evidence')
    ->monthlyOn(1, '07:30')
    ->timezone('Europe/Amsterdam');

// Elk uur: ruim verlopen demo-omgevingen op.
Schedule::command('demo:cleanup')->hourly();

// Hartslag van de planner: /health meldt 'degraded' zodra dit stempel ouder is
// dan een kwartier — zo valt een stilgevallen schedule:work direct op.
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever(\App\Http\Controllers\HealthController::HEARTBEAT_KEY, now()->timestamp))
    ->everyFiveMinutes()
    ->name('scheduler-heartbeat');

// Elke nacht 03:30: database-back-up naar de externe opslag (zie config services.backup).
Schedule::command('backup:run')
    ->dailyAt('03:30')
    ->timezone('Europe/Amsterdam')
    ->withoutOverlapping();

// Drie keer per dag (PSD2 staat vier onbeheerde synchronisaties per dag toe):
// nieuwe banktransacties ophalen voor administraties met een Ponto-koppeling.
Schedule::command('ponto:sync')
    ->cron('40 6,12,18 * * *')
    ->timezone('Europe/Amsterdam')
    ->withoutOverlapping();
