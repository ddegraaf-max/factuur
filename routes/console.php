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

// Elk uur: ruim verlopen demo-omgevingen op.
Schedule::command('demo:cleanup')->hourly();
