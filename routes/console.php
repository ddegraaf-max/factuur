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
