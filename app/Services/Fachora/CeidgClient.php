<?php

namespace App\Services\Fachora;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CEIDG API v3 (dane.biznes.gov.pl): de eenmanszaken, veruit de meeste
 * vakmannen. Zoeken op PKD-code en województwo/plaats, met een JWT-token dat
 * je na registratie op biznes.gov.pl krijgt (CEIDG_TOKEN).
 *
 * De bedrijven komen als 'unclaimed' in de database; telefoon en e-mail staan
 * er alleen bij als de ondernemer die zelf openbaar heeft gemaakt, en ook dan
 * mailt Fachora ze niet (ConsentPolicy). Veldnamen volgens de documentatie
 * van de API; wijkt de echte API af, dan vangt normalize() dat op zonder te
 * breken (ontbrekende velden blijven leeg).
 */
class CeidgClient
{
    public function __construct(private ProviderService $providers) {}

    public function configured(): bool
    {
        return filled(config('fachora.ceidg_token'));
    }

    /**
     * Eén pagina zoekresultaten.
     *
     * @param  array<string, mixed>  $query  bijv. ['pkd' => '4322Z', 'wojewodztwo' => 'łódzkie', 'status' => 'AKTYWNY']
     * @param  int  $page  de API telt vanaf 0 (links.next van de eerste pagina wijst naar page=1)
     * @return array{firms: list<array<string, mixed>>, count: int}
     */
    public function search(array $query, int $page = 0, int $limit = 25): array
    {
        $response = Http::withToken((string) config('fachora.ceidg_token'))
            ->acceptJson()->timeout(30)
            ->get(config('fachora.ceidg_url') . '/firmy', array_filter($query) + ['page' => $page, 'limit' => $limit]);

        if (! $response->successful()) {
            throw new \RuntimeException('CEIDG: ' . $response->status() . ' ' . mb_substr($response->body(), 0, 200));
        }

        $json = (array) $response->json();

        return [
            'firms' => array_values((array) ($json['firmy'] ?? $json['firma'] ?? [])),
            'count' => (int) ($json['count'] ?? count((array) ($json['firmy'] ?? []))),
        ];
    }

    /**
     * Van een CEIDG-record naar een importrecord (zie ProviderService::importRecords).
     *
     * De lijst /firmy geeft alleen id, nazwa, adresDzialalnosci, wlasciciel (imie, nazwisko,
     * nip, regon), dataRozpoczecia, status en link; PKD-codes, telefoon en e-mail staan alleen
     * in het detailrecord. Daarom gaan de gezochte PKD-codes als terugval mee: de categorie
     * volgt dan uit de zoekopdracht. Telefoon en e-mail blijven leeg (brieven gaan per post).
     *
     * @param  array<string, mixed>  $firm
     * @param  list<string>  $fallbackPkd  PKD-codes van de zoekopdracht
     * @return array<string, mixed>
     */
    public function normalize(array $firm, array $fallbackPkd = []): array
    {
        $address = (array) ($firm['adresDzialalnosci'] ?? $firm['adresKorespondencyjny'] ?? []);
        $owner = (array) ($firm['wlasciciel'] ?? []);
        $pkd = collect((array) ($firm['pkd'] ?? []))->map(fn ($p) => is_array($p) ? ($p['kod'] ?? '') : (string) $p)->filter()->values()->all();
        if (isset($firm['pkdGlowny']['kod'])) {
            array_unshift($pkd, $firm['pkdGlowny']['kod']);
        }
        if ($pkd === []) {
            $pkd = array_values(array_filter(array_map('strval', $fallbackPkd)));
        }
        $street = trim(implode(' ', array_filter([$address['ulica'] ?? null, $address['budynek'] ?? null])));
        if (! empty($address['lokal'])) {
            $street .= '/' . $address['lokal'];
        }

        return [
            'name' => (string) ($firm['nazwa'] ?? trim(($owner['imie'] ?? '') . ' ' . ($owner['nazwisko'] ?? ''))),
            'legal_name' => (string) ($firm['nazwa'] ?? ''),
            'nip' => ($owner['nip'] ?? $firm['nip'] ?? null) ?: null,
            'regon' => ($owner['regon'] ?? $firm['regon'] ?? null) ?: null,
            'legal_form' => 'jdg',
            'address' => $street ?: null,
            'postal_code' => $address['kod'] ?? null,
            'city' => $address['miasto'] ?? null,
            'voivodeship' => $address['wojewodztwo'] ?? null,
            'phone' => $firm['telefon'] ?? null,
            'email' => filter_var($firm['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $firm['email'] : null,
            'website' => $firm['www'] ?? null,
            'categories' => self::categoriesForPkd($pkd),
            'consent' => false,
            'source' => 'ceidg',
            'note' => 'CEIDG ' . ($firm['status'] ?? '') . ' · PKD ' . implode(', ', array_slice($pkd, 0, 4)),
        ];
    }

    /**
     * Importeert alle actieve bedrijven met een PKD-code in een gebied.
     *
     * @return array{created: int, skipped: int, pages: int}
     */
    public function import(string $pkd, ?string $voivodeship = null, ?string $city = null, int $maxPages = 20): array
    {
        $created = $skipped = $pages = 0;
        for ($page = 0; $page < $maxPages; $page++) {
            try {
                $result = $this->search(['pkd' => str_replace('.', '', $pkd), 'wojewodztwo' => $voivodeship, 'miasto' => $city, 'status' => 'AKTYWNY'], $page);
            } catch (\Throwable $e) {
                Log::warning('CEIDG-import gestopt', ['pkd' => $pkd, 'page' => $page, 'error' => $e->getMessage()]);
                break;
            }
            $pages++;
            if ($result['firms'] === []) {
                break;
            }
            $counts = $this->providers->importRecords(array_map(fn ($f) => $this->normalize($f, [$pkd]), $result['firms']), 'ceidg');
            $created += $counts['created'];
            $skipped += $counts['skipped'];
            // Niet sneller dan de dienst prettig vindt.
            usleep(500_000);
        }

        return compact('created', 'skipped', 'pages');
    }

    /** PKD-codes (met of zonder punten) naar Fachora-categorieën. @return list<string> */
    public static function categoriesForPkd(array $codes): array
    {
        $normalise = fn (string $c) => strtoupper(str_replace(['.', ' '], '', $c));
        $wanted = array_map($normalise, $codes);
        $out = [];
        foreach ((array) config('fachora.categories', []) as $key => $cat) {
            foreach ((array) ($cat['pkd'] ?? []) as $code) {
                if (in_array($normalise($code), $wanted, true)) {
                    $out[] = $key;
                    break;
                }
            }
        }

        return $out;
    }
}
