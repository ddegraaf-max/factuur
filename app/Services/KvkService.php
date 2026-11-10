<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Officiële KvK API (developers.kvk.nl):
 *
 *  - Zoeken API (gratis)        → bedrijven zoeken op naam of KvK-nummer.
 *  - Basisprofiel API (optioneel) → volledig vestigingsadres voor het
 *    automatisch invullen. Niet besteld/beschikbaar? Dan vallen we terug op
 *    de adresgegevens uit het zoekresultaat (straat + plaats).
 *
 * Zonder KVK_API_KEY is de hele functie onzichtbaar in de interface.
 *
 * Het basisprofiel kost geld per bevraging en gaat daarom altijd langs de
 * kostenbewaker (CostGuard, dienst 'kvk'); zoeken is gratis en telt niet mee
 * voor de kosten, maar heeft een grens per minuut ('kvk_zoeken').
 */
class KvkService
{
    private ?string $refusal = null;

    public function enabled(): bool
    {
        return filled(config('services.kvk.key'));
    }

    /**
     * Zoek op bedrijfsnaam of (8-cijferig) KvK-nummer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '' || ! $this->enabled()) {
            return [];
        }

        $cacheKey = 'kvk:zoek:'.sha1(mb_strtolower($query));

        return Cache::remember($cacheKey, 300, function () use ($query) {
            // Zoeken is gratis, maar gaat met onze sleutel: grens per minuut.
            try {
                app(CostGuard::class)->spend('kvk_zoeken', CostGuard::currentTeam());
            } catch (CostCapReached $e) {
                throw new \DomainException($e->getMessage());
            }

            $params = preg_match('/^\d{8}$/', str_replace(' ', '', $query))
                ? ['kvkNummer' => str_replace(' ', '', $query)]
                : ['naam' => $query];
            $params['resultatenPerPagina'] = 10;

            $response = Http::timeout(8)
                ->withHeaders(['apikey' => config('services.kvk.key')])
                ->get(config('services.kvk.base').'/api/v2/zoeken', $params);

            // "Niets gevonden" komt terug als fout IPD5200 — dat is geen storing.
            if ($response->failed()) {
                if (str_contains($response->body(), 'IPD5200')) {
                    return [];
                }
                Log::warning('KvK Zoeken API-fout', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);
                throw new \DomainException(__('Het KvK-register reageert niet. Probeer het zo opnieuw.'));
            }

            $results = [];
            foreach ($response->json('resultaten', []) as $item) {
                // De rechtspersoon-regel dubbelt met de hoofdvestiging en heeft
                // zelf geen adres — overslaan als er ook vestigingen zijn.
                $address = $item['adres']['binnenlandsAdres'] ?? [];

                $results[] = [
                    'kvk_number' => $item['kvkNummer'] ?? null,
                    'name' => $item['naam'] ?? '',
                    'type' => $item['type'] ?? '',
                    'street' => $address['straatnaam'] ?? null,
                    'city' => $address['plaats'] ?? null,
                ];
            }

            // Ontdubbel: als een KvK-nummer zowel als rechtspersoon als met een
            // (hoofd)vestiging voorkomt, is de vestiging (mét adres) het nuttigst.
            $withBranch = collect($results)->where('type', '!=', 'rechtspersoon')->pluck('kvk_number')->all();

            return collect($results)
                ->reject(fn ($r) => $r['type'] === 'rechtspersoon' && in_array($r['kvk_number'], $withBranch, true))
                ->values()
                ->all();
        });
    }

    /**
     * Wat het Handelsregister over een bedrijf zegt, voor de klantscore: hoe
     * lang het bestaat, de rechtsvorm, het aantal mensen en of het nog
     * bestaat. Zonder Basisprofiel-API (of zonder sleutel) null.
     *
     * $fresh ververst het geheugen hoogstens één keer per 24 uur per nummer
     * per team; daarbinnen komt het onthouden antwoord terug.
     *
     * @return array{name: ?string, legal_form: ?string, registered_on: ?string, started_on: ?string, ended_on: ?string, employees: ?int, active: bool, checked_at: string}|null
     */
    public function facts(string $kvkNumber, bool $fresh = false, int|string|null $team = null): ?array
    {
        $kvkNumber = preg_replace('/\D/', '', $kvkNumber) ?? '';
        $profile = $this->basisprofiel($kvkNumber, $fresh, $team);
        if ($profile === null) {
            return null;
        }
        $data = $profile['data'];

        // Datums komen als jjjjmmdd.
        $date = fn ($raw) => preg_match('/^(\d{4})(\d{2})(\d{2})$/', (string) $raw, $m) ? "{$m[1]}-{$m[2]}-{$m[3]}" : null;
        $ended = $date($data['materieleRegistratie']['datumEinde'] ?? null) ?? $date($data['_embedded']['eigenaar']['datumUitschrijving'] ?? null);

        return [
            'name' => $data['naam'] ?? ($data['statutaireNaam'] ?? null),
            'legal_form' => $data['_embedded']['eigenaar']['uitgebreideRechtsvorm'] ?? ($data['_embedded']['eigenaar']['rechtsvorm'] ?? null),
            'registered_on' => $date($data['formeleRegistratiedatum'] ?? null),
            'started_on' => $date($data['materieleRegistratie']['datumAanvang'] ?? null),
            'ended_on' => $ended,
            'employees' => isset($data['totaalWerkzamePersonen']) ? (int) $data['totaalWerkzamePersonen'] : null,
            'active' => $ended === null,
            'checked_at' => $profile['checked_at'],
        ];
    }

    /**
     * Volledige bedrijfsgegevens voor het invullen van het klantformulier.
     * Basisprofiel niet beschikbaar (of plafond bereikt, zie refusal())? Dan
     * null en gebruikt het frontend het zoekresultaat.
     */
    public function profile(string $kvkNumber, int|string|null $team = null): ?array
    {
        $profile = $this->basisprofiel($kvkNumber, false, $team);
        if ($profile === null) {
            return null;
        }
        $data = $profile['data'];
        $addresses = $data['_embedded']['hoofdvestiging']['adressen'] ?? [];

        // Bezoekadres heeft de voorkeur boven een postbus.
        $address = collect($addresses)->firstWhere('type', 'bezoekadres')
            ?? collect($addresses)->first();

        $line = null;
        $postalCode = null;
        $city = null;

        if ($address) {
            if (! empty($address['postbusnummer'])) {
                $line = 'Postbus '.$address['postbusnummer'];
            } else {
                $line = trim(implode(' ', array_filter([
                    $address['straatnaam'] ?? null,
                    $address['huisnummer'] ?? null,
                    $address['huisletter'] ?? null,
                    $address['huisnummerToevoeging'] ?? null,
                ])));
            }
            $postalCode = $address['postcode'] ?? null;
            if ($postalCode && preg_match('/^(\d{4})([A-Z]{2})$/i', $postalCode, $m)) {
                $postalCode = $m[1].' '.strtoupper($m[2]);
            }
            $city = $address['plaats'] ?? null;
        }

        return [
            'kvk_number' => $data['kvkNummer'] ?? $kvkNumber,
            'name' => $data['naam'] ?? ($data['statutaireNaam'] ?? null),
            'address_line' => $line ?: null,
            'postal_code' => $postalCode,
            'city' => $city,
        ];
    }

    /**
     * De melding voor de gebruiker als de laatste opvraging niet naar buiten
     * mocht (noodstop of plafond). Null als er niets is geweigerd.
     */
    public function refusal(): ?string
    {
        return $this->refusal;
    }

    /**
     * Het betaalde basisprofiel (€ 0,02 per bevraging), gedeeld door profile()
     * en facts(). Eén geheugen per KvK-nummer: een gevonden bedrijf 7 dagen,
     * "niet gevonden" 24 uur. Een storing (time-out, 5xx) wordt niet onthouden.
     * Elke echte bevraging gaat eerst langs de kostenbewaker.
     *
     * @return array{data: array<string, mixed>, checked_at: string}|null
     */
    private function basisprofiel(string $kvkNumber, bool $fresh, int|string|null $team): ?array
    {
        $this->refusal = null;
        if (! preg_match('/^\d{8}$/', $kvkNumber) || ! $this->enabled()) {
            return null;
        }
        $team ??= CostGuard::currentTeam();
        $key = "kvk:basisprofiel:{$kvkNumber}";

        try {
            $cached = Cache::get($key);
        } catch (\Throwable) {
            $cached = null;
        }
        $cached = is_array($cached) && array_key_exists('found', $cached) ? $cached : null;

        // Verversen: hoogstens één keer per 24 uur per nummer per team (ook als er nog niets onthouden was).
        $refresh = $fresh && $this->mayRefresh($kvkNumber, $team);
        if ($cached !== null && ! $refresh) {
            return $cached['found'] ? $cached : null;
        }

        try {
            app(CostGuard::class)->spend('kvk', $team);
        } catch (CostCapReached $e) {
            $this->refusal = $e->getMessage();

            // Verversen mocht niet meer: dan het oude antwoord.
            return $cached && $cached['found'] ? $cached : null;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['apikey' => config('services.kvk.key')])
                ->get(config('services.kvk.base')."/api/v1/basisprofielen/{$kvkNumber}");
        } catch (\Throwable $e) {
            Log::info('KvK Basisprofiel niet bereikbaar', ['kvk' => $kvkNumber, 'error' => $e->getMessage()]);

            return $cached && $cached['found'] ? $cached : null;
        }

        if (in_array($response->status(), [400, 404], true)) {
            // Bestaat niet (of is geen geldig nummer): onthouden, anders kost
            // nummers aflopen elke keer opnieuw geld.
            $this->remember($key, ['found' => false, 'checked_at' => now()->toIso8601String()], now()->addDay());

            return null;
        }
        if ($response->failed()) {
            // Storing, of de Basisprofiel API is niet besteld (403): stil terugvallen, niet onthouden.
            Log::info('KvK Basisprofiel niet beschikbaar', ['kvk' => $kvkNumber, 'status' => $response->status()]);

            return $cached && $cached['found'] ? $cached : null;
        }

        $data = $response->json();
        if (! is_array($data)) {
            return null;
        }
        $profile = ['found' => true, 'data' => $data, 'checked_at' => now()->toIso8601String()];
        $this->remember($key, $profile, now()->addDays(7));

        return $profile;
    }

    /** Mag dit team dit nummer nu verversen? Hoogstens één keer per 24 uur. */
    private function mayRefresh(string $kvkNumber, int|string|null $team): bool
    {
        try {
            return Cache::add('kvk:ververst:'.($team ?? 'geen').":{$kvkNumber}", true, now()->addDay());
        } catch (\Throwable) {
            return false;
        }
    }

    private function remember(string $key, array $value, \DateTimeInterface $until): void
    {
        try {
            Cache::put($key, $value, $until);
        } catch (\Throwable $e) {
            Log::warning('KvK: antwoord niet onthouden', ['error' => $e->getMessage()]);
        }
    }
}
