<?php

namespace App\Services\Fachora;

use App\Models\Provider;
use App\Services\NipService;
use App\Support\Fachora;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
    public const CURSOR = 'fachora:region-import';

    public function __construct(private ProviderService $providers, private ?GeocodeService $geo = null)
    {
        $this->geo ??= app(GeocodeService::class);
    }

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

    /** Het detailrecord van één bedrijf (met PKD-codes, telefoon en e-mail), of null. @return array<string, mixed>|null */
    public function detail(string $id): ?array
    {
        $response = Http::withToken((string) config('fachora.ceidg_token'))
            ->acceptJson()->timeout(30)
            ->get(config('fachora.ceidg_url') . '/firma/' . rawurlencode($id));
        if (! $response->successful()) {
            return null;
        }
        $json = (array) $response->json();
        $firm = $json['firma'] ?? $json;
        if (is_array($firm) && array_is_list($firm)) {
            $firm = $firm[0] ?? null;
        }

        return is_array($firm) ? $firm : null;
    }

    /** Alle PKD-codes van de categorieën, zonder dubbele. @return list<string> */
    public static function allPkdCodes(): array
    {
        return collect((array) config('fachora.categories', []))->flatMap(fn ($c) => (array) ($c['pkd'] ?? []))->unique()->values()->all();
    }

    /**
     * Eén portie van de regio-import (1.86, planner): de startregio's uit
     * config/fachora.php, alle PKD-codes, pagina na pagina. Alleen actieve
     * bedrijven die minstens import_min_years bestaan, binnen region_radius_km
     * van het middelpunt en met een e-mailadres in CEIDG. De voortgang staat in
     * de cache (CURSOR); wie al in de database staat (NIP), wordt overgeslagen
     * zonder het detailrecord op te halen.
     *
     * @return array{created: int, skipped: int, checked: int, done: bool, at: string}
     */
    public function importRegionsStep(?int $firmBudget = null, int $pageBudget = 12): array
    {
        $regions = array_values((array) config('fachora.regions', []));
        $codes = self::allPkdCodes();
        $cursor = (array) Cache::get(self::CURSOR, ['r' => 0, 'c' => 0, 'p' => 0, 'done' => false]);
        $firmBudget ??= (int) config('fachora.import_firms_per_run', 150);
        $radius = (float) config('fachora.region_radius_km', 50);
        $minStart = now()->subYears((int) config('fachora.import_min_years', 1));
        $created = $skipped = $checked = 0;

        while (! ($cursor['done'] ?? false) && $firmBudget > 0 && $pageBudget > 0) {
            $region = $regions[$cursor['r']] ?? null;
            $pkd = $codes[$cursor['c']] ?? null;
            if ($region === null) {
                $cursor['done'] = true;
                break;
            }
            if ($pkd === null) {
                $cursor = ['r' => $cursor['r'] + 1, 'c' => 0, 'p' => 0, 'done' => false];
                continue;
            }

            try {
                $result = $this->search(['pkd' => str_replace('.', '', $pkd), 'wojewodztwo' => $region['voivodeship'] ?? null, 'status' => 'AKTYWNY'], (int) $cursor['p']);
            } catch (\Throwable $e) {
                Log::warning('CEIDG regio-import gepauzeerd', ['region' => $region['name'] ?? '?', 'pkd' => $pkd, 'page' => $cursor['p'], 'error' => $e->getMessage()]);
                break; // volgende run opnieuw
            }
            $pageBudget--;

            if ($result['firms'] === []) {
                $cursor = ['r' => $cursor['r'], 'c' => $cursor['c'] + 1, 'p' => 0, 'done' => false];
                continue;
            }

            foreach ($result['firms'] as $firm) {
                $outcome = $this->importFirm((array) $firm, $pkd, $region, $radius, $minStart);
                if ($outcome === 'created') {
                    $created++;
                } else {
                    $skipped++;
                }
                if ($outcome !== 'known') {
                    $checked++;
                    $firmBudget--;
                }
            }
            $cursor['p']++;
            // Laatste pagina (minder dan een volle pagina of het totaal bereikt): door naar de volgende code.
            if (count($result['firms']) < 25 || ($result['count'] > 0 && $cursor['p'] * 25 >= $result['count'])) {
                $cursor = ['r' => $cursor['r'], 'c' => $cursor['c'] + 1, 'p' => 0, 'done' => false];
            }
            usleep(300_000);
        }

        Cache::forever(self::CURSOR, $cursor);
        $at = isset($regions[$cursor['r']]) ? ($regions[$cursor['r']]['name'] ?? '?') . ' · ' . ($codes[$cursor['c']] ?? '-') . ' · pagina ' . $cursor['p'] : 'klaar';

        return ['created' => $created, 'skipped' => $skipped, 'checked' => $checked, 'done' => (bool) ($cursor['done'] ?? false), 'at' => $at];
    }

    /**
     * Eén bedrijf uit de lijst: filteren en zo nodig toevoegen.
     *
     * @param  array<string, mixed>  $firm
     * @param  array<string, mixed>  $region
     * @return 'created'|'known'|'old'|'far'|'no_email'|'no_detail'
     */
    public function importFirm(array $firm, string $pkd, array $region, float $radius, Carbon $minStart): string
    {
        $owner = (array) ($firm['wlasciciel'] ?? []);
        $nip = NipService::normalize($owner['nip'] ?? $firm['nip'] ?? null) ?: null;
        if ($nip !== null && Provider::query()->where('nip', $nip)->exists()) {
            return 'known';
        }

        // Te jong (of geen startdatum): niet in de eerste lichting.
        $started = $firm['dataRozpoczecia'] ?? null;
        try {
            if (! $started || Carbon::parse($started)->gt($minStart)) {
                return 'old';
            }
        } catch (\Throwable) {
            return 'old';
        }

        $address = (array) ($firm['adresDzialalnosci'] ?? []);
        [$clat, $clng] = $region['center'];
        $coords = $this->geo->fallback($address['miasto'] ?? null);
        if ($coords !== null && Fachora::distanceKm($clat, $clng, $coords['lat'], $coords['lng']) > $radius) {
            return 'far';
        }

        $detail = filled($firm['id'] ?? null) ? $this->detail((string) $firm['id']) : null;
        usleep(200_000);
        if ($detail === null) {
            return 'no_detail';
        }
        $record = $this->normalize(array_merge($firm, $detail), [$pkd]);
        if (blank($record['email'])) {
            return 'no_email';
        }

        $coords ??= $this->geo->lookup($record['postal_code'] ?? null, $record['city'] ?? null);
        if ($coords === null || Fachora::distanceKm($clat, $clng, $coords['lat'], $coords['lng']) > $radius) {
            return 'far';
        }

        $counts = $this->providers->importRecords([$record + ['lat' => $coords['lat'], 'lng' => $coords['lng']]], 'ceidg');

        return $counts['created'] > 0 ? 'created' : 'known';
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
