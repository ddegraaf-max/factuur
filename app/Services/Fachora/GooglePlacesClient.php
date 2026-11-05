<?php

namespace App\Services\Fachora;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Support\Fachora;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fachora 1.103: bedrijven met een goede online reputatie, uit hun Google Business-profiel
 * (Places API New, Text Search). Eenmanszaken uit CEIDG reageerden niet op klanten; bedrijven
 * met veel en goede reviews wel. Alleen bedrijven met minstens google_min_rating en
 * google_min_reviews, die open zijn en een telefoonnummer of website hebben.
 *
 * Toestemmingsregel blijft: Fachora mailt of sms't deze bedrijven nooit; de klant neemt zelf
 * contact op (ConsentPolicy::mayShowDirectContact). Places-beleid: opgeslagen gegevens
 * (behalve het place-id) binnen 30 dagen verversen of weggooien — zie refreshStale().
 */
class GooglePlacesClient
{
    public const SOURCE = 'google';

    private const SEARCH_URL = 'https://places.googleapis.com/v1/places:searchText';

    private const DETAILS_URL = 'https://places.googleapis.com/v1/places/';

    private const FIELDS = ['id', 'displayName', 'formattedAddress', 'addressComponents', 'location', 'rating', 'userRatingCount',
        'nationalPhoneNumber', 'internationalPhoneNumber', 'websiteUri', 'googleMapsUri', 'businessStatus'];

    public function __construct(private ProviderService $providers) {}

    public function configured(): bool
    {
        return filled(config('fachora.google_places_key'));
    }

    /** Per categorie en plaats hooguit eens per local_import_days zoeken (ook bij een nieuwe klant op dezelfde plek). */
    public static function cacheKey(ServiceJob $job): string
    {
        return 'fachora:google-import:' . $job->category . ':' . GeocodeService::normalise((string) $job->city);
    }

    public function recentlySearched(ServiceJob $job): bool
    {
        return Cache::has(self::cacheKey($job));
    }

    /** Zoektermen (Pools) voor een categorie: config fachora.google_terms, anders de categorienaam. */
    public static function termsFor(string $category): array
    {
        $terms = (array) config("fachora.google_terms.{$category}", []);

        return $terms !== [] ? array_values($terms) : [Fachora::categoryName($category, 'pl')];
    }

    /**
     * Rond een klant zoeken (import op aanvraag): per zoekterm hooguit google_pages pagina's,
     * tot er target kandidaten zijn. Per categorie en plaats hooguit eens per local_import_days.
     *
     * @return array{created: int, updated: int, found: int, candidates: int, skipped: ?string}
     */
    public function importAround(ServiceJob $job, ?int $target = null): array
    {
        $target ??= (int) config('fachora.local_import_target', 10);
        $count = fn () => $this->providers->choiceCandidates($job, $target)->count();
        $result = ['created' => 0, 'updated' => 0, 'found' => 0, 'candidates' => $count(), 'skipped' => null];
        if (! $this->configured()) {
            return ['skipped' => 'no_key'] + $result;
        }
        if ($job->lat === null || $job->lng === null || blank($job->city)) {
            return ['skipped' => 'no_location'] + $result;
        }
        $key = self::cacheKey($job);
        if (Cache::has($key)) {
            return ['skipped' => 'recent'] + $result;
        }

        $outcome = $this->importArea($job->category, (string) $job->city, [(float) $job->lat, (float) $job->lng], fn () => $count() >= $target);
        Cache::put($key, now()->toDateTimeString(), now()->addDays((int) config('fachora.local_import_days', 14)));
        $result = array_merge($result, $outcome);
        $result['candidates'] = $count();

        return $result;
    }

    /**
     * Eén gebied doorzoeken: "{term} {stad}" met een cirkel als voorkeur. Bedrijven buiten
     * anderhalf keer de straal vallen af (Google geeft soms verre treffers).
     *
     * @param  array{0: float, 1: float}|null  $center
     * @return array{created: int, updated: int, found: int}
     */
    public function importArea(string $category, string $city, ?array $center, ?\Closure $enough = null): array
    {
        $result = ['created' => 0, 'updated' => 0, 'found' => 0];
        $radius = (float) config('fachora.google_radius_km', 30);
        $pages = max(1, (int) config('fachora.google_pages', 3));
        foreach (self::termsFor($category) as $term) {
            $token = null;
            for ($page = 0; $page < $pages; $page++) {
                if ($enough && $enough()) {
                    return $result;
                }
                try {
                    $found = $this->searchText(trim($term . ' ' . $city), $center, $radius, $token);
                } catch (\Throwable $e) {
                    Log::warning('Google Places-zoekopdracht mislukt', ['category' => $category, 'city' => $city, 'term' => $term, 'error' => $e->getMessage()]);

                    return $result;
                }
                foreach ($found['places'] as $place) {
                    $result['found']++;
                    $outcome = $this->upsert($place, $category, $center, $radius * 1.5);
                    if ($outcome === 'created') {
                        $result['created']++;
                    } elseif ($outcome === 'updated') {
                        $result['updated']++;
                    }
                }
                $token = $found['next'];
                if ($token === null) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Text Search (Places API New). Alleen velden die we gebruiken (de field mask bepaalt de prijs).
     *
     * @param  array{0: float, 1: float}|null  $center
     * @return array{places: list<array<string, mixed>>, next: ?string}
     */
    public function searchText(string $query, ?array $center, float $radiusKm, ?string $pageToken = null): array
    {
        $body = ['textQuery' => $query, 'languageCode' => 'pl', 'regionCode' => 'PL', 'pageSize' => 20];
        if ($center !== null) {
            $body['locationBias'] = ['circle' => ['center' => ['latitude' => $center[0], 'longitude' => $center[1]], 'radius' => min(50000, max(1000, $radiusKm * 1000))]];
        }
        if ($pageToken !== null) {
            $body['pageToken'] = $pageToken;
        }
        $mask = implode(',', array_map(fn ($f) => 'places.' . $f, self::FIELDS)) . ',nextPageToken';
        $response = Http::timeout(12)->retry(2, 500)
            ->withHeaders(['X-Goog-Api-Key' => (string) config('fachora.google_places_key'), 'X-Goog-FieldMask' => $mask])
            ->post(self::SEARCH_URL, $body)->throw();
        $json = (array) $response->json();

        return ['places' => array_values((array) ($json['places'] ?? [])), 'next' => filled($json['nextPageToken'] ?? null) ? (string) $json['nextPageToken'] : null];
    }

    /** Place Details voor het verversen (zelfde velden). Null als Google het bedrijf niet meer kent. */
    public function details(string $placeId): ?array
    {
        $response = Http::timeout(12)
            ->withHeaders(['X-Goog-Api-Key' => (string) config('fachora.google_places_key'), 'X-Goog-FieldMask' => implode(',', self::FIELDS)])
            ->get(self::DETAILS_URL . rawurlencode($placeId));
        if ($response->status() === 404) {
            return null;
        }

        return (array) $response->throw()->json();
    }

    /**
     * Een Google-resultaat opslaan of bijwerken. 'skipped' als het bedrijf niet voldoet (beoordeling,
     * aantal reviews, gesloten, geen contact, te ver) of al onder een andere bron bekend is.
     *
     * @param  array<string, mixed>  $place
     * @param  array{0: float, 1: float}|null  $center
     */
    public function upsert(array $place, string $category, ?array $center = null, ?float $maxKm = null): string
    {
        $attrs = self::attributes($place);
        if ($attrs === null) {
            return 'skipped';
        }
        if ($center !== null && $maxKm !== null && $attrs['lat'] !== null
            && Fachora::distanceKm($center[0], $center[1], $attrs['lat'], $attrs['lng']) > $maxKm) {
            return 'skipped';
        }

        $existing = Provider::query()->where('source', self::SOURCE)->where('source_id', $attrs['source_id'])->first();
        if ($existing) {
            $existing->forceFill(array_diff_key($attrs, array_flip(['source', 'source_id'])) + ['ext_refreshed_at' => now()])->save();
            if (! in_array($category, $existing->categories(), true)) {
                $existing->syncCategories(array_merge($existing->categories(), [$category]));
            }

            return 'updated';
        }
        // Al bekend als Verified/geclaimd/registerbedrijf (zelfde telefoon of website)? Dan niet dubbel.
        if ($this->knownElsewhere($attrs['phone'], $attrs['website'])) {
            return 'skipped';
        }
        $this->providers->createFromRegister($attrs + ['ext_refreshed_at' => now()], [$category]);

        return 'created';
    }

    /**
     * Van een Places-resultaat naar providerkolommen; null als het bedrijf niet voldoet.
     *
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>|null
     */
    public static function attributes(array $place): ?array
    {
        $rating = isset($place['rating']) ? (float) $place['rating'] : null;
        $count = isset($place['userRatingCount']) ? (int) $place['userRatingCount'] : 0;
        $phone = self::phone($place['nationalPhoneNumber'] ?? $place['internationalPhoneNumber'] ?? null);
        $website = self::website($place['websiteUri'] ?? null);
        if (blank($place['id'] ?? null) || blank($place['displayName']['text'] ?? null)
            || ($place['businessStatus'] ?? 'OPERATIONAL') !== 'OPERATIONAL'
            || $rating === null || $rating < (float) config('fachora.google_min_rating', 4.5)
            || $count < (int) config('fachora.google_min_reviews', 10)
            || ($phone === null && $website === null)) {
            return null;
        }
        $components = [];
        foreach ((array) ($place['addressComponents'] ?? []) as $c) {
            foreach ((array) ($c['types'] ?? []) as $type) {
                $components[$type] = (string) ($c['longText'] ?? $c['shortText'] ?? '');
            }
        }
        $street = trim(($components['route'] ?? '') . ' ' . ($components['street_number'] ?? ''));
        $city = $components['locality'] ?? $components['postal_town'] ?? $components['administrative_area_level_2'] ?? null;
        $name = mb_substr((string) $place['displayName']['text'], 0, 160);

        return [
            'display_name' => $name,
            'legal_name' => $name,
            'address_line' => $street !== '' ? mb_substr($street, 0, 200) : (isset($place['formattedAddress']) ? mb_substr(Str::before((string) $place['formattedAddress'], ','), 0, 200) : null),
            'postal_code' => isset($components['postal_code']) ? mb_substr($components['postal_code'], 0, 10) : null,
            'city' => $city !== null ? mb_substr($city, 0, 120) : null,
            'voivodeship' => isset($components['administrative_area_level_1']) ? mb_substr(Str::of($components['administrative_area_level_1'])->lower()->replaceFirst('województwo ', ''), 0, 60) : null,
            'lat' => isset($place['location']['latitude']) ? (float) $place['location']['latitude'] : null,
            'lng' => isset($place['location']['longitude']) ? (float) $place['location']['longitude'] : null,
            'phone' => $phone,
            'website' => $website,
            'email' => null,
            'source' => self::SOURCE,
            'source_id' => mb_substr((string) $place['id'], 0, 64),
            'ext_rating' => round($rating, 1),
            'ext_rating_count' => $count,
            'ext_maps_url' => isset($place['googleMapsUri']) ? mb_substr((string) $place['googleMapsUri'], 0, 300) : null,
            'registry_status' => 'active',
        ];
    }

    /**
     * Gewogen beoordeling (Bayesiaans): weinig reviews trekken naar het gemiddelde van 4,3, zodat
     * 5,0 met 10 reviews niet boven 4,8 met 300 reviews staat.
     */
    public static function weightedRating(?float $rating, ?int $count): float
    {
        if ($rating === null || ! $count) {
            return 0.0;
        }
        $m = 25;

        return round(($count / ($count + $m)) * $rating + ($m / ($count + $m)) * 4.3, 3);
    }

    /**
     * Verversen (Places-beleid: binnen 30 dagen): beoordeling, aantal reviews, contact en status.
     * Gesloten of onbekend bij Google → uit de lijst (removed_at), tenzij geclaimd.
     *
     * @return array{refreshed: int, removed: int, failed: int}
     */
    public function refreshStale(int $limit = 150): array
    {
        $result = ['refreshed' => 0, 'removed' => 0, 'failed' => 0];
        if (! $this->configured()) {
            return $result;
        }
        $stale = Provider::query()->where('source', self::SOURCE)->whereNull('claimed_at')->whereNull('removed_at')
            ->where(fn ($q) => $q->whereNull('ext_refreshed_at')->orWhere('ext_refreshed_at', '<', now()->subDays((int) config('fachora.google_refresh_days', 25))))
            ->orderBy('ext_refreshed_at')->limit($limit)->get();
        foreach ($stale as $provider) {
            try {
                $place = $this->details((string) $provider->source_id);
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('Google Places verversen mislukt', ['provider' => $provider->id, 'error' => $e->getMessage()]);
                continue;
            }
            $attrs = $place ? self::attributes($place) : null;
            if ($attrs === null) {
                // Gesloten, verdwenen of niet meer goed genoeg: niet meer aanbieden (gegevens vallen onder het 30-dagenbeleid).
                $provider->forceFill(['removed_at' => now(), 'ext_refreshed_at' => now(), 'status_reason' => 'google: niet meer beschikbaar of onder de drempel'])->save();
                $result['removed']++;
                continue;
            }
            $provider->forceFill(array_diff_key($attrs, array_flip(['source', 'source_id', 'email'])) + ['ext_refreshed_at' => now()])->save();
            $result['refreshed']++;
        }

        return $result;
    }

    private function knownElsewhere(?string $phone, ?string $website): bool
    {
        $query = Provider::query()->where('source', '!=', self::SOURCE)->whereNull('removed_at');
        $digits = $phone !== null ? substr(preg_replace('/\D+/', '', $phone), -9) : null;
        $host = self::host($website);
        if ($digits === null && $host === null) {
            return false;
        }

        return $query->where(function ($q) use ($digits, $host) {
            if ($digits !== null) {
                $stripped = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";
                $q->orWhereRaw($stripped . ' LIKE ?', ['%' . $digits]);
            }
            if ($host !== null) {
                $q->orWhere('website', 'like', '%' . $host . '%');
            }
        })->exists();
    }

    private static function phone(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        return $raw !== '' && preg_match('/\d{6,}/', preg_replace('/\D+/', '', $raw)) ? mb_substr($raw, 0, 40) : null;
    }

    private static function website(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '' || ! preg_match('#^https?://#i', $raw)) {
            return null;
        }
        // Social-media- en Google-adressen tellen niet als eigen website.
        if (preg_match('#(facebook|instagram|business\.site|google\.|goo\.gl|linktr\.ee)#i', $raw)) {
            return null;
        }

        return mb_substr($raw, 0, 180);
    }

    public static function host(?string $url): ?string
    {
        $host = $url !== null ? strtolower((string) parse_url($url, PHP_URL_HOST)) : '';
        $host = preg_replace('/^www\./', '', $host);

        return $host !== '' ? $host : null;
    }
}
