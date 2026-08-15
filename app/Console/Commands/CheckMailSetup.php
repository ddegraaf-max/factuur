<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Diagnose van de e-mailinstellingen.
 *
 * Draai dit in de Railway-console als je twijfelt of e-mail werkt:
 *   php artisan mail:check jouw@adres.nl
 */
class CheckMailSetup extends Command
{
    protected $signature = 'mail:check {email? : Stuur een testbericht naar dit adres}';

    protected $description = 'Controleer de e-mailinstellingen en verstuur eventueel een testbericht.';

    public function handle(): int
    {
        $problems = [];

        $this->newLine();
        $this->line('<options=bold>1. Mailconfiguratie</>');

        $mailer = config('mail.default');
        $from = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->table(['Instelling', 'Waarde'], [
            ['MAIL_MAILER', $mailer],
            ['Host', config('mail.mailers.smtp.host') ?: '—'],
            ['Poort', config('mail.mailers.smtp.port') ?: '—'],
            ['Gebruikersnaam', config('mail.mailers.smtp.username') ?: '—'],
            ['Wachtwoord (API-key)', config('mail.mailers.smtp.password') ? 'ingesteld' : 'LEEG'],
            ['Afzenderadres', $from],
            ['Afzendernaam', $fromName],
        ]);

        if ($mailer === 'log') {
            $problems[] = 'MAIL_MAILER staat op "log": e-mail wordt naar het logbestand geschreven en NIET verstuurd. Zet MAIL_MAILER=smtp.';
        }
        if (! config('mail.mailers.smtp.password') && $mailer === 'smtp') {
            $problems[] = 'MAIL_PASSWORD is leeg — zonder Resend-API-key kan er niets verstuurd worden.';
        }
        if (str_contains((string) $from, 'resend.dev')) {
            $problems[] = 'Het afzenderadres is een Resend-testadres. Daarmee komt post alleen aan op je eigen account, niet bij klanten. Gebruik een adres op een geverifieerd domein.';
        }
        if (str_contains((string) $from, '.test')) {
            $problems[] = 'MAIL_FROM_ADDRESS lijkt niet ingevuld (valt terug op de standaardwaarde).';
        }

        // ---------- 2. Resend: key en domeinen ----------
        $this->newLine();
        $this->line('<options=bold>2. Resend-account</>');

        $key = config('services.resend.key') ?: config('mail.mailers.smtp.password');

        if (! $key) {
            $this->warn('Geen API-key gevonden — overgeslagen.');
            $problems[] = 'Geen Resend-API-key (RESEND_KEY of MAIL_PASSWORD).';
        } else {
            try {
                $response = Http::withToken($key)->acceptJson()->timeout(15)->get('https://api.resend.com/domains');

                if ($response->status() === 401 || $response->status() === 403) {
                    $this->error('De API-key wordt geweigerd door Resend (ongeldig of ingetrokken).');
                    $problems[] = 'Resend weigert de API-key. Maak een nieuwe aan in het Resend-dashboard.';
                } elseif ($response->failed()) {
                    $this->warn('Kon de domeinen niet ophalen: HTTP '.$response->status());
                } else {
                    $domains = $response->json('data') ?? [];

                    if (empty($domains)) {
                        $this->warn('Er zijn geen domeinen geregistreerd in dit Resend-account.');
                        $problems[] = 'Geen geverifieerd domein in Resend — versturen naar klanten zal mislukken.';
                    } else {
                        $rows = [];
                        $fromDomain = strtolower(substr(strrchr((string) $from, '@') ?: '', 1));
                        $matched = false;

                        foreach ($domains as $d) {
                            $name = $d['name'] ?? '?';
                            $status = $d['status'] ?? '?';
                            $isFrom = strtolower($name) === $fromDomain;
                            $matched = $matched || ($isFrom && $status === 'verified');
                            $rows[] = [$name, $status, $isFrom ? '← afzenderdomein' : ''];
                        }

                        $this->table(['Domein', 'Status', ''], $rows);

                        if (! $matched) {
                            $problems[] = "Het domein van je afzenderadres ({$fromDomain}) is niet als 'verified' teruggekomen. Controleer de DNS-records (SPF/DKIM) in Resend.";
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->warn('Resend niet bereikbaar: '.$e->getMessage());
            }
        }

        // ---------- 3. Testbericht ----------
        $email = $this->argument('email');

        if ($email) {
            $this->newLine();
            $this->line('<options=bold>3. Testbericht</>');

            try {
                Mail::raw(
                    "Dit is een testbericht van EasyInvoice.\n\n"
                    ."Als je dit leest, werkt de e-mailconfiguratie.\n"
                    ."Verstuurd op: ".now()->toDateTimeString()."\n"
                    ."Afzender: {$fromName} <{$from}>",
                    fn ($m) => $m->to($email)->subject('EasyInvoice — testbericht')
                );

                $this->info("Verstuurd naar {$email}.");
                if ($mailer === 'log') {
                    $this->warn('Let op: dit ging naar storage/logs/laravel.log, niet naar de mailbox.');
                } else {
                    $this->line('Kijk ook in de spammap als het bericht niet aankomt.');
                }
            } catch (\Throwable $e) {
                $this->error('Versturen mislukt: '.$e->getMessage());
                $problems[] = 'Het testbericht kon niet worden verstuurd: '.$e->getMessage();
            }
        } else {
            $this->newLine();
            $this->comment('Tip: geef een adres mee om een testbericht te sturen — php artisan mail:check jij@bedrijf.nl');
        }

        // ---------- Samenvatting ----------
        $this->newLine();
        if (empty($problems)) {
            $this->info('✓ Geen problemen gevonden in de e-mailinstellingen.');

            return self::SUCCESS;
        }

        $this->error('Gevonden aandachtspunten:');
        foreach ($problems as $i => $problem) {
            $this->line('  '.($i + 1).'. '.$problem);
        }

        return self::FAILURE;
    }
}
