<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\JobService;
use App\Services\Fachora\ProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fachora 1.87: import op aanvraag. Heeft een klant niemand om uit te kiezen,
 * dan zoekt de planner in CEIDG rond zijn plaats (en powiat) en mailt daarna
 * de keuze; vindt hij niemand, dan gaat de aanvraag de gewone weg.
 */
class FachoraLocalImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false,
            'services.anthropic.key' => null, 'services.stripe.secret' => null,
            'services.smstools.client_id' => null, 'services.smstools.client_secret' => null,
            'services.marketing_stats.emails' => 'eigenaar@example.com',
            'fachora.customer_choice' => true, 'fachora.request_fee' => 0,
            'fachora.ceidg_token' => 'jwt', 'fachora.local_import' => true,
            'fachora.categories' => ['hydraulik' => ['pl' => 'Hydraulik', 'en' => 'Plumber', 'icon' => 'tap', 'pkd' => ['43.22.Z']]],
        ]);
        Cache::flush();
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-10-22 10:00'));
    }

    private function fakeCeidg(array $firms): void
    {
        $firm = fn (string $id, string $nip) => [
            'id' => $id, 'nazwa' => "Firma $id", 'dataRozpoczecia' => '2018-03-01', 'status' => 'AKTYWNY',
            'adresDzialalnosci' => ['miasto' => 'Police', 'kod' => '72-010', 'ulica' => 'ul. Wojska Polskiego', 'budynek' => '5'],
            'wlasciciel' => ['imie' => 'Jan', 'nazwisko' => "Kowalski $id", 'nip' => $nip],
        ];
        $fakes = [
            'nominatim.openstreetmap.org/reverse*' => Http::response(['address' => ['county' => 'powiat policki']], 200),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '53.5521', 'lon' => '14.5718']], 200),
            'dane.biznes.gov.pl/api/ceidg/v3/firmy*' => Http::response(['firmy' => array_map(fn ($f) => $firm(...$f), $firms), 'count' => count($firms)], 200),
        ];
        foreach ($firms as [$id]) {
            $fakes["dane.biznes.gov.pl/api/ceidg/v3/firma/$id"] = Http::response(['firma' => [['email' => "biuro@$id.pl", 'pkd' => [['kod' => '43.22.Z']]]]], 200);
        }
        Http::fake($fakes + ['*' => Http::response([], 200)]);
    }

    private function confirmedJob(): ServiceJob
    {
        $job = ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => 'hydraulik', 'title' => 'Kran', 'description' => 'Kran w kuchni cieknie, potrzebna wymiana.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '72-010', 'city' => 'Police', 'lat' => 53.5521, 'lng' => 14.5718,
        ]);
        app(JobService::class)->confirm($job, request());

        return $job->fresh();
    }

    public function test_without_nearby_companies_the_customer_waits_for_the_search_and_then_chooses(): void
    {
        $this->fakeCeidg([['hydro-police', '5260250274']]);
        $job = $this->confirmedJob();
        $this->assertSame('selecting', $job->status);
        $this->assertNotNull($job->provider_search_at);
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'choose');

        $this->artisan('fachora:local-search')->assertSuccessful();

        $job->refresh();
        $this->assertNull($job->provider_search_at);
        $this->assertSame('selecting', $job->status);
        $provider = Provider::query()->where('nip', '5260250274')->firstOrFail();
        $this->assertSame('biuro@hydro-police.pl', $provider->email);
        $this->assertSame([$provider->id], app(ProviderService::class)->choiceCandidates($job)->pluck('id')->all());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'choose' && $m->hasTo('anna@example.com'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/firmy') && str_contains(urldecode($r->url()), 'miasto=Police'));
    }

    public function test_when_the_register_has_nobody_the_request_takes_the_normal_route(): void
    {
        $this->fakeCeidg([]);
        $job = $this->confirmedJob();
        $this->artisan('fachora:local-search')->assertSuccessful();

        $job->refresh();
        $this->assertNull($job->provider_search_at);
        $this->assertSame('no_match', $job->status);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'no_match' && $m->hasTo('anna@example.com'));
        // De powiat is ook geprobeerd.
        Http::assertSent(fn ($r) => str_contains(urldecode($r->url()), 'powiat=policki'));
    }

    public function test_a_search_that_takes_too_long_is_finished_without_calling_the_register(): void
    {
        $this->fakeCeidg([['hydro-police', '5260250274']]);
        $job = $this->confirmedJob();
        $this->travel(20)->minutes();
        $this->artisan('fachora:local-search')->assertSuccessful();

        $this->assertNull($job->fresh()->provider_search_at);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'dane.biznes.gov.pl'));
    }

    public function test_with_few_companies_the_customer_chooses_right_away_and_the_area_is_topped_up(): void
    {
        $this->fakeCeidg([['hydro-police', '5260250274']]);
        $slug = 'hydro-bestaand-'.Str::lower(Str::random(4));
        Provider::create([
            'legal_name' => 'Hydro Bestaand', 'display_name' => 'Hydro Bestaand', 'slug' => $slug, 'status' => 'unclaimed',
            'email' => "$slug@example.com", 'city' => 'Police', 'postal_code' => '72-010', 'lat' => 53.5521, 'lng' => 14.5718,
            'radius_km' => 25, 'source' => 'ceidg', 'optout_token' => bin2hex(random_bytes(32)),
        ])->syncCategories(['hydraulik']);

        $job = $this->confirmedJob();
        $this->assertNull($job->provider_search_at);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'choose');

        $this->artisan('fachora:local-search')->assertSuccessful();
        $this->assertSame(2, app(ProviderService::class)->choiceCandidates($job->fresh())->count());

        // Dezelfde plaats en categorie: niet meteen opnieuw het register in.
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => Http::response([], 200)]);
        $second = $this->confirmedJob();
        $this->artisan('fachora:local-search')->assertSuccessful();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'dane.biznes.gov.pl'));
        $this->assertSame('selecting', $second->fresh()->status);
    }
}
