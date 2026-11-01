<?php

namespace App\Console\Commands;

use App\Services\DemoAccess;
use Illuminate\Console\Command;

/**
 * Eigenaar via de terminal: een goedgekeurde demolink maken en mailen zonder
 * aanvraag, bijv. `railway ssh -- php artisan demo:invite naam@voorbeeld.nl`.
 * Handig voor jezelf of voor een controle van de live demo.
 */
class DemoInvite extends Command
{
    protected $signature = 'demo:invite {email : Wie de link krijgt} {--name= : Naam in de aanvraag} {--locale=nl : Taal van de mail (nl, en, pl)}';

    protected $description = 'Maakt een goedgekeurde demolink (14 dagen) en mailt die naar het adres';

    public function handle(DemoAccess $access): int
    {
        $request = $access->invite((string) $this->argument('email'), $this->option('name') ?: null, (string) $this->option('locale'));

        $this->info('Demolink voor '.$request->email.' (geldig tot '.$request->expires_at?->toDateString().'): '.route('demo.landing', $request->token));

        return self::SUCCESS;
    }
}
