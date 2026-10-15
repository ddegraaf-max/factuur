<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\GeocodeService;
use App\Services\Fachora\JobService;
use App\Services\Fachora\ProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Welke bedrijven een opdracht krijgen: alleen Fachora Verified, in de
 * categorie, binnen hun eigen werkstraal, bij voorkeur in de taal van de
 * klant, op volgorde van beoordeling; en de geocoder met zijn vangnet.
 */
class FachoraMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-10-16 10:00'));
    }

    private function fakeGeocoderDown(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
            '*' => Http::response([], 200),
        ]);
    }

    private function verifiedProvider(string $name, array $attrs = [], array $categories = ['hydraulik']): Provider
    {
        $slug = Str::slug($name).'-'.Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'verified',
            'email' => $slug.'@example.com', 'city' => 'Łódź', 'postal_code' => '90-001',
            'lat' => 51.7592, 'lng' => 19.4560, 'radius_km' => 25, 'languages' => ['pl' => 'native', 'en' => 'b2'],
            'claimed_at' => now()->subMonth(), 'consent_at' => now()->subMonth(), 'consent_source' => 'claim',
            'verified_at' => now()->subDays(100), 'optout_token' => bin2hex(random_bytes(32)), 'protect_opt_in' => true,
        ]);
        $provider->syncCategories($categories);

        return $provider->refresh();
    }

    private function openJob(array $attrs = []): ServiceJob
    {
        return ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'open',
            'category' => 'hydraulik', 'title' => 'Wyciek pod zlewem', 'description' => 'Kapie pod zlewem w kuchni, potrzebna szybka naprawa.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Kowalska', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '90-001', 'city' => 'Łódź', 'lat' => 51.7592, 'lng' => 19.4560, 'confirmed_at' => now(),
        ]);
    }

    public function test_match_takes_only_verified_companies_in_the_category_within_their_own_radius_with_a_working_address(): void
    {
        $this->fakeGeocoderDown();
        $lodz = $this->verifiedProvider('Hydro Łódź');
        $this->verifiedProvider('Elektro Łódź', [], ['elektryk']);
        $this->verifiedProvider('Hydro Warszawa', ['city' => 'Warszawa', 'lat' => 52.2297, 'lng' => 21.0122]);
        $wide = $this->verifiedProvider('Hydro Warszawa Cała Polska', ['city' => 'Warszawa', 'lat' => 52.2297, 'lng' => 21.0122, 'radius_km' => 200]);
        $this->verifiedProvider('Hydro Claimed', ['status' => 'claimed']);
        $this->verifiedProvider('Hydro Suspended', ['status' => 'suspended']);
        $this->verifiedProvider('Hydro Zonder Mail', ['email' => null]);
        $this->verifiedProvider('Hydro Afgemeld', ['email_blocked_at' => now()->subDay()]);
        $this->verifiedProvider('Hydro Zonder Coördinaten', ['lat' => null, 'lng' => null]);

        $matches = app(ProviderService::class)->match($this->openJob(), 5);

        $this->assertSame([$lodz->id, $wide->id], $matches->pluck('id')->all());
        $this->assertEqualsWithDelta(0.0, $matches[0]->distance_km, 0.1);
        $this->assertEqualsWithDelta(118.0, $matches[1]->distance_km, 5.0, 'Łódź–Warschau is zo\'n 118 km');

        // Al aangeboden bedrijven worden overgeslagen.
        $this->assertSame([$wide->id], app(ProviderService::class)->match($this->openJob(), 5, [$lodz->id])->pluck('id')->all());
    }

    public function test_without_coordinates_the_city_name_decides(): void
    {
        $this->fakeGeocoderDown();
        $pabianice = $this->verifiedProvider('Hydro Pabianice', ['city' => 'Pabianice', 'lat' => null, 'lng' => null]);
        $this->verifiedProvider('Hydro Zgierz', ['city' => 'Zgierz', 'lat' => null, 'lng' => null]);

        $matches = app(ProviderService::class)->match($this->openJob(['city' => 'pabianice', 'lat' => null, 'lng' => null, 'postal_code' => null]), 5);

        $this->assertSame([$pabianice->id], $matches->pluck('id')->all());
        $this->assertNull($matches[0]->distance_km);
    }

    public function test_the_language_of_the_customer_is_a_hard_filter_with_a_fallback_when_nobody_speaks_it(): void
    {
        $this->fakeGeocoderDown();
        $polishOnly = $this->verifiedProvider('Tylko Polski', ['languages' => ['pl' => 'native']]);
        $german = $this->verifiedProvider('Deutsch Sprecher', ['languages' => ['pl' => 'native', 'de' => 'b1']]);
        $service = app(ProviderService::class);

        $this->assertSame([$german->id], $service->match($this->openJob(['language' => 'de']), 5)->pluck('id')->all());
        $this->assertCount(2, $service->match($this->openJob(['language' => 'pl']), 5), 'Pools spreekt iedereen');

        // Niemand spreekt Nederlands: eerst niets, dan biedt dispatch het toch aan en markeert de opdracht.
        $job = $this->openJob(['language' => 'nl']);
        $this->assertTrue($service->match($job, 5)->isEmpty());
        $sent = app(JobService::class)->dispatch($job);

        $this->assertSame(2, $sent);
        $job->refresh();
        $this->assertSame('open', $job->status);
        $this->assertTrue((bool) ($job->ai['language_fallback'] ?? false));
        $this->assertEqualsCanonicalizing([$polishOnly->id, $german->id], $job->offers()->pluck('provider_id')->all());
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job' && $m->hasTo($polishOnly->email));

        // Met een Duitse spreker is er voor een Duitse klant geen terugval nodig.
        $jobDe = $this->openJob(['language' => 'de']);
        app(JobService::class)->dispatch($jobDe);
        $this->assertSame([$german->id], $jobDe->offers()->pluck('provider_id')->all());
        $this->assertFalse((bool) ($jobDe->fresh()->ai['language_fallback'] ?? false));
    }

    public function test_at_most_max_offers_companies_ordered_by_rating_then_response_time(): void
    {
        $this->fakeGeocoderDown();
        config(['fachora.max_offers' => 3]);
        $ratings = ['Vier' => 4.0, 'Vijf' => 5.0, 'Drie' => 3.0, 'Geen' => null, 'VierHalf' => 4.5, 'VierSnel' => 4.0];
        $byName = [];
        foreach ($ratings as $name => $rating) {
            $byName[$name] = $this->verifiedProvider($name, ['rating_avg' => $rating, 'rating_count' => $rating ? 5 : 0, 'response_minutes' => $name === 'VierSnel' ? 30 : null]);
        }

        $job = $this->openJob();
        $matches = app(ProviderService::class)->match($job, 10);
        $this->assertSame(
            [$byName['Vijf']->id, $byName['VierHalf']->id, $byName['VierSnel']->id, $byName['Vier']->id, $byName['Drie']->id, $byName['Geen']->id],
            $matches->pluck('id')->all(),
            'Hoogste beoordeling eerst; bij gelijke beoordeling wie sneller reageert'
        );

        $this->assertSame(3, app(JobService::class)->dispatch($job));
        $this->assertEqualsCanonicalizing([$byName['Vijf']->id, $byName['VierHalf']->id, $byName['VierSnel']->id], $job->offers()->pluck('provider_id')->all());
        $this->assertSame(3, Mail::sent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'job')->count());
    }

    public function test_the_geocoder_result_is_used_and_cached(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '51.7600', 'lon' => '19.4600', 'display_name' => 'Łódź']], 200),
            '*' => Http::response([], 200),
        ]);
        $geo = app(GeocodeService::class);

        $hit = $geo->lookup('90-001', 'Łódź');
        $this->assertEqualsWithDelta(51.76, $hit['lat'], 0.0001);
        $this->assertEqualsWithDelta(19.46, $hit['lng'], 0.0001);
        $geo->lookup('90-001', 'Łódź');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'countrycodes=pl') && str_contains($request->url(), 'postalcode=90-001') && $request->hasHeader('User-Agent'));

        $this->assertNull($geo->lookup('', ''));
    }

    public function test_without_a_geocoder_the_city_table_is_the_safety_net_and_pending_providers_get_coordinates(): void
    {
        $this->fakeGeocoderDown();
        $geo = app(GeocodeService::class);

        $this->assertEqualsWithDelta(52.2297, $geo->lookup(null, 'Warszawa')['lat'], 0.0001);
        $this->assertEqualsWithDelta(52.2297, $geo->lookup('02-500', 'Warszawa Mokotów')['lat'], 0.0001, 'Het eerste woord telt');
        $this->assertEqualsWithDelta(50.0647, $geo->lookup(null, 'KRAKÓW')['lat'], 0.0001, 'Zonder diakrieten en hoofdletters');
        $this->assertNull($geo->lookup('00-000', 'Nieznane Miasteczko'));
        $this->assertNull($geo->fallback(null));
        $this->assertNull($geo->fallback('   '));

        $krakow = Provider::create(['legal_name' => 'Hydro Kraków', 'display_name' => 'Hydro Kraków', 'slug' => 'hydro-krakow', 'status' => 'unclaimed', 'city' => 'Kraków']);
        $nowhere = Provider::create(['legal_name' => 'Nigdzie', 'display_name' => 'Nigdzie', 'slug' => 'nigdzie', 'status' => 'unclaimed', 'city' => 'Nieznane Miasteczko']);
        $none = Provider::create(['legal_name' => 'Bez adresu', 'display_name' => 'Bez adresu', 'slug' => 'bez-adresu', 'status' => 'unclaimed']);

        $this->assertSame(1, app(ProviderService::class)->geocodePending());
        $this->assertEqualsWithDelta(50.0647, $krakow->fresh()->lat, 0.0001);
        $this->assertNull($nowhere->fresh()->lat);
        $this->assertNull($none->fresh()->lat);
    }
}
