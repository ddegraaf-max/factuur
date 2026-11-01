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
    protected $signature = 'demo:invite {email : Wie de link krijgt} {--name= : Naam in de aanvraag} {--locale=nl : Taal van de mail (nl, en, pl)} {--days=14 : Hoeveel dagen de link werkt} {--no-mail : Alleen de link tonen, niet mailen (bijv. voor de rookproef)}';

    protected $description = 'Maakt een goedgekeurde demolink en mailt die naar het adres (of toont hem alleen)';

    public function handle(DemoAccess $access): int
    {
        $request = $access->invite(
            (string) $this->argument('email'),
            $this->option('name') ?: null,
            (string) $this->option('locale'),
            max(1, (int) $this->option('days')),
            ! $this->option('no-mail'),
        );

        $this->info('Demolink voor '.$request->email.' (geldig tot '.$request->expires_at?->toDateString().', '.($this->option('no-mail') ? 'niet gemaild' : 'gemaild').'): '.route('demo.landing', $request->token));
        $this->line('Token: '.$request->token);

        return self::SUCCESS;
    }
}
