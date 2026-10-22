<?php

namespace App\Services\Fachora;

use App\Models\Provider;
use App\Models\ServiceJob;
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

    /**
     * Import op aanvraag (1.87): bedrijven in de categorie van deze klus rond
     * de klant, eerst in zijn plaats, daarna in zijn powiat. Zelfde filters als
     * de regio-import (actief, oud genoeg, e-mail, binnen region_radius_km van
     * de klant). Stopt zodra er genoeg kandidaten zijn of het budget op is.
     * Per categorie en plaats hooguit eens per local_import_days.
     *
     * @return array{created: int, checked: int, candidates: int, skipped: ?string}
     */
    public function importAround(ServiceJob $job, ?int $target = null, ?int $firmBudget = null, int $pageBudget = 8): array
    {
        $target ??= (int) config('fachora.local_import_target', 10);
        $firmBudget ??= (int) config('fachora.local_import_firms', 60);
        $count = fn () => $this->providers->choiceCandidates($job, $target)->count();
        $result = ['created' => 0, 'checked' => 0, 'candidates' => $count(), 'skipped' => null];

        if (! $this->configured()) {
            return ['skipped' => 'no_token'] + $result;
        }
        $codes = (array) (config('fachora.categories.' . $job->category . '.pkd') ?? []);
        $center = $job->lat !== null && $job->lng !== null ? [(float) $job->lat, (float) $job->lng] : null;
        if ($codes === [] || $center === null || blank($job->city)) {
            return ['skipped' => 'no_location'] + $result;
        }
        $key = 'fachora:local-import:' . $job->category . ':' . GeocodeService::normalise($job->city);
        if (Cache::has($key)) {
            return ['skipped' => 'recent'] + $result;
        }

        $region = ['name' => $job->city, 'center' => $center];
        $radius = (float) config('fachora.region_radius_km', 50);
        $minStart = now()->subYears((int) config('fachora.import_min_years', 1));
        $areas = [['miasto' => trim((string) $job->city)]];
        if ($county = $this->geo->county($center[0], $center[1])) {
            $areas[] = ['powiat' => $county];
        }

        foreach ($areas as $area) {
            foreach ($codes as $pkd) {
                for ($page = 0; $page < 40; $page++) {
                    if ($firmBudget <= 0 || $pageBudget <= 0 || $result['candidates'] >= $target) {
                        break 3;
                    }
                    try {
                        $found = $this->search($area + ['pkd' => str_replace('.', '', $pkd), 'status' => 'AKTYWNY'], $page);
                    } catch (\Throwable $e) {
                        Log::warning('CEIDG import op aanvraag mislukt', ['job' => $job->id, 'area' => $area, 'pkd' => $pkd, 'error' => $e->getMessage()]);
                        break 3; // niet als "gedaan" markeren: volgende klant probeert het opnieuw
                    }
                    $pageBudget--;
                    foreach ($found['firms'] as $firm) {
                        $outcome = $this->importFirm((array) $firm, $pkd, $region, $radius, $minStart);
                        if ($outcome === 'created') {
                            $result['created']++;
                        }
                        if ($outcome !== 'known') {
                            $result['checked']++;
                            $firmBudget--;
                        }
                    }
                    if ($result['created'] > 0) {
                        $result['candidates'] = $count();
                    }
                    if (count($found['firms']) < 25) {
                        break; // laatste pagina van deze code
                    }
                }
            }
        }

        if (! isset($e)) {
            Cache::put($key, true, now()->addDays((int) config('fachora.local_import_days', 14)));
        }
        Log::info('Fachora import op aanvraag', ['job' => $job->id, 'city' => $job->city, 'category' => $job->category] + $result);

        return $result;
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
