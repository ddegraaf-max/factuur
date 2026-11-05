<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\ConsentPolicy;
use App\Services\Fachora\GooglePlacesClient;
use App\Services\Fachora\JobService;
use App\Services\Fachora\ProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Fachora 1.103: bedrijven met goede Google-beoordelingen (Places API) komen vóór eenmanszaken uit
 * het register; die laatste alleen als reserve. Fachora mailt of sms't ze nooit; de klant belt of
 * opent de website. Gegevens worden binnen 30 dagen ververst (Places-beleid).
 */
class FachoraGooglePlacesTest extends TestCase
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
            'fachora.customer_choice' => true, 'fachora.email_unregistered' => true, 'fachora.request_fee' => 0,
            'fachora.ceidg_token' => null, 'fachora.local_import' => true, 'fachora.google_places_key' => 'test-key',
            'fachora.google_min_rating' => 4.5, 'fachora.google_min_reviews' => 10, 'fachora.register_fallback_min' => 5,
            'fachora.categories' => ['hydraulik' => ['pl' => 'Hydraulik', 'en' => 'Plumber', 'icon' => 'tap', 'pkd' => ['43.22.Z']]],
            'fachora.google_terms' => ['hydraulik' => ['hydraulik']],
        ]);
        Cache::flush();
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-11-05 10:00'));
    }

    /** @return array<string, mixed> */
    private function place(string $id, string $name, float $rating, int $count, array $extra = []): array
    {
        return $extra + [
            'id' => $id, 'displayName' => ['text' => $name, 'languageCode' => 'pl'],
            'formattedAddress' => 'ul. Długa 12, 70-001 Szczecin, Polska',
            'addressComponents' => [
                ['longText' => 'Długa', 'types' => ['route']], ['longText' => '12', 'types' => ['street_number']],
                ['longText' => 'Szczecin', 'types' => ['locality']], ['longText' => '70-001', 'types' => ['postal_code']],
                ['longText' => 'Województwo zachodniopomorskie', 'types' => ['administrative_area_level_1']],
            ],
            'location' => ['latitude' => 53.43, 'longitude' => 14.55],
            'rating' => $rating, 'userRatingCount' => $count,
            'nationalPhoneNumber' => '91 400 00 ' . substr($id, -2),
            'websiteUri' => 'https://' . strtolower($id) . '.pl/',
            'googleMapsUri' => 'https://maps.google.com/?cid=' . $id,
            'businessStatus' => 'OPERATIONAL',
        ];
    }

    private function fakeGoogle(array $places, array $details = []): void
    {
        $fakes = [
            'nominatim.openstreetmap.org/reverse*' => Http::response(['address' => ['county' => 'Szczecin']], 200),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '53.4285', 'lon' => '14.5528']], 200),
            'places.googleapis.com/v1/places:searchText*' => Http::response(['places' => $places], 200),
        ];
        foreach ($details as $id => $response) {
            $fakes['places.googleapis.com/v1/places/' . $id] = $response;
        }
        Http::fake($fakes + ['*' => Http::response([], 200)]);
    }

    private function registerProvider(string $name): Provider
    {
        $provider = Provider::create([
            'legal_name' => $name, 'display_name' => $name, 'slug' => Provider::makeSlug($name, 'Szczecin'), 'status' => 'unclaimed',
            'city' => 'Szczecin', 'lat' => 53.4285, 'lng' => 14.5528, 'radius_km' => 25, 'source' => 'ceidg', 'legal_form' => 'jdg',
            'email' => 'biuro@' . str_replace(' ', '', strtolower($name)) . '.pl', 'pkd_main' => '43.22.Z', 'optout_token' => bin2hex(random_bytes(16)),
        ]);
        $provider->syncCategories(['hydraulik']);

        return $provider;
    }

    private function confirmedJob(): ServiceJob
    {
        $job = ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => 'hydraulik', 'title' => 'Kran', 'description' => 'Kran w kuchni cieknie, potrzebna wymiana.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '70-001', 'city' => 'Szczecin', 'lat' => 53.4285, 'lng' => 14.5528,
        ]);
        app(JobService::class)->confirm($job, request());

        return $job->fresh();
    }

    public function test_well_rated_google_companies_are_imported_and_offered_before_register_sole_traders(): void
    {
        $this->fakeGoogle([
            $this->place('ChA', 'Hydro-Max Szczecin', 4.9, 120),
            $this->place('ChB', 'Instal Nowak', 4.6, 15, ['nationalPhoneNumber' => null]), // alleen een website: ook goed
            $this->place('ChC', 'Tani Hydraulik', 3.9, 200), // te lage beoordeling
            $this->place('ChD', 'Nowa Firma', 5.0, 4), // te weinig reviews
            $this->place('ChE', 'Zamknięta', 4.8, 90, ['businessStatus' => 'CLOSED_PERMANENTLY']),
            $this->place('ChF', 'Daleko', 4.8, 90, ['location' => ['latitude' => 52.23, 'longitude' => 21.01]]), // Warschau: te ver
        ]);
        $register = $this->registerProvider('Jan Kowalski Hydraulika');
        $job = $this->confirmedJob();
        $this->assertSame('selecting', $job->status, 'één registerbedrijf: de klant kan kiezen, de aanvulling loopt op de achtergrond');

        app(JobService::class)->runLocalSearches();
        $google = Provider::query()->where('source', 'google')->orderBy('display_name')->get();
        $this->assertSame(['Hydro-Max Szczecin', 'Instal Nowak'], $google->pluck('display_name')->all());
        $max = $google->firstWhere('display_name', 'Hydro-Max Szczecin');
        $this->assertSame('ChA', $max->source_id);
        $this->assertSame(4.9, (float) $max->ext_rating);
        $this->assertSame(120, $max->ext_rating_count);
        $this->assertSame('Szczecin', $max->city);
        $this->assertSame('70-001', $max->postal_code);
        $this->assertSame('Długa 12', $max->address_line);
        $this->assertSame('zachodniopomorskie', $max->voivodeship);
        $this->assertSame('https://cha.pl/', $max->website);
        $this->assertNull($max->email);
        $this->assertSame(['hydraulik'], $max->categories());
        $this->assertNotNull($max->ext_refreshed_at);
        $this->assertSame('main', $max->pkdFit('hydraulik'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'places:searchText') && $r->hasHeader('X-Goog-Api-Key', 'test-key') && $r['textQuery'] === 'hydraulik Szczecin');

        // Keuzelijst: Google-bedrijven op gewogen beoordeling, het registerbedrijf erachter (minder dan 5 anderen).
        $names = app(ProviderService::class)->choiceCandidates($job)->pluck('display_name')->all();
        $this->assertSame(['Hydro-Max Szczecin', 'Instal Nowak', 'Jan Kowalski Hydraulika'], $names);
        $this->get(route('fachora.job.show', $job->token))->assertOk()->assertInertia(fn ($page) => $page
            ->where('choice.candidates.0.google.rating', 4.9)->where('choice.candidates.0.google.count', 120)
            ->where('choice.candidates.0.google.url', 'https://maps.google.com/?cid=ChA')
            ->where('choice.candidates.0.website', 'https://cha.pl/')->where('choice.candidates.0.url', null)->where('choice.candidates.0.source', 'google')
            ->where('choice.candidates.2.source', 'ceidg')->where('choice.candidates.2.google', null));

        // Genoeg goed beoordeelde bedrijven? Dan blijft het register weg.
        config(['fachora.register_fallback_min' => 2]);
        $this->assertSame(['Hydro-Max Szczecin', 'Instal Nowak'], app(ProviderService::class)->choiceCandidates($job)->pluck('display_name')->all());

        // Nooit mailen, wel zelf contact (ook met alleen een website); geen eigen profielpagina.
        $nowak = $google->firstWhere('display_name', 'Instal Nowak');
        $this->assertFalse(ConsentPolicy::mayEmailRequest($nowak));
        $this->assertTrue(ConsentPolicy::mayShowDirectContact($nowak));
        $this->get('/firma/' . $max->slug)->assertNotFound();
        $this->get('/firma/' . $register->slug)->assertOk();

        // Dezelfde zoektocht binnen veertien dagen niet nog eens.
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => []], 200)]);
        $result = app(GooglePlacesClient::class)->importAround($job);
        $this->assertSame('recent', $result['skipped']);
    }

    public function test_the_customer_can_contact_a_google_company_via_its_website_and_the_message_has_no_profile_line(): void
    {
        $this->fakeGoogle([$this->place('ChA', 'Hydro-Max Szczecin', 4.9, 120)]);
        $job = $this->confirmedJob();
        $this->assertNotNull($job->provider_search_at, 'niemand in de buurt: eerst zoeken');
        app(JobService::class)->runLocalSearches();
        $job->refresh();
        $this->assertNull($job->provider_search_at);
        $google = Provider::query()->where('source', 'google')->firstOrFail();

        $this->post(route('fachora.job.select', $job->token), ['providers' => [$google->id]])->assertRedirect();
        $job->refresh();
        $this->assertSame('open', $job->status);
        $this->assertSame(0, $job->offers()->count(), 'een Google-bedrijf krijgt geen aanbieding of mail');
        Mail::assertNotSent(\App\Mail\FachoraMail::class, fn ($m) => $m->hasTo('biuro@cha.pl'));

        $this->get(route('fachora.job.show', $job->token))->assertOk()->assertInertia(fn ($page) => $page
            ->where('direct.0.website', 'https://cha.pl/')->where('direct.0.google.count', 120)->where('direct.0.url', null)->where('direct.0.email', null));
        $this->post(route('fachora.job.direct.contacted', [$job->token, $google->id]), ['channel' => 'website'])->assertRedirect();
        $this->assertContains('website', app(JobService::class)->directContacted($job->fresh(), $google->id)['channels']);
    }

    public function test_refresh_updates_ratings_and_drops_closed_or_vanished_companies(): void
    {
        $client = app(GooglePlacesClient::class);
        foreach ([['ChA', 'Hydro-Max Szczecin', 4.9, 120], ['ChB', 'Instal Nowak', 4.6, 15], ['ChC', 'Stary Zakład', 4.7, 30]] as [$id, $name, $rating, $count]) {
            $this->assertSame('created', $client->upsert($this->place($id, $name, $rating, $count), 'hydraulik'));
        }
        Provider::query()->where('source', 'google')->update(['ext_refreshed_at' => now()->subDays(40)]);
        $this->fakeGoogle([], [
            'ChA' => Http::response($this->place('ChA', 'Hydro-Max Szczecin', 4.7, 150, ['nationalPhoneNumber' => '91 555 00 00']), 200),
            'ChB' => Http::response(['error' => ['code' => 404]], 404),
            'ChC' => Http::response($this->place('ChC', 'Stary Zakład', 4.7, 30, ['businessStatus' => 'CLOSED_TEMPORARILY']), 200),
        ]);

        $this->artisan('fachora:google-refresh')->assertSuccessful();
        $a = Provider::query()->where('source_id', 'ChA')->firstOrFail();
        $this->assertSame(4.7, (float) $a->ext_rating);
        $this->assertSame(150, $a->ext_rating_count);
        $this->assertSame('91 555 00 00', $a->phone);
        $this->assertTrue($a->ext_refreshed_at->isSameDay(now()));
        $this->assertNull($a->removed_at);
        $this->assertNotNull(Provider::query()->where('source_id', 'ChB')->firstOrFail()->removed_at, 'verdwenen bij Google');
        $this->assertNotNull(Provider::query()->where('source_id', 'ChC')->firstOrFail()->removed_at, 'gesloten');
        Http::assertSentCount(3);

        // Een al bekend (Verified) bedrijf met hetzelfde nummer wordt niet nog eens als Google-bedrijf opgeslagen.
        $this->registerProvider('Bekend Bedrijf')->forceFill(['phone' => '+48 91 400 00 77'])->save();
        $this->assertSame('skipped', $client->upsert($this->place('ChX', 'Bekend Bedrijf', 4.8, 50, ['nationalPhoneNumber' => '91 400 00 77']), 'hydraulik'));
        $this->assertSame(1.0 * round((120 / 145) * 4.9 + (25 / 145) * 4.3, 3), GooglePlacesClient::weightedRating(4.9, 120));
    }

    public function test_the_import_command_fills_a_city_and_needs_a_key(): void
    {
        $this->fakeGoogle([$this->place('ChA', 'Hydro-Max Szczecin', 4.9, 120), $this->place('ChB', 'Instal Nowak', 4.6, 15)]);
        $this->artisan('fachora:google-import', ['category' => 'hydraulik', 'city' => 'Szczecin'])->assertSuccessful();
        $this->assertSame(2, Provider::query()->where('source', 'google')->count());
        $this->artisan('fachora:google-import', ['category' => 'hydraulik', 'city' => 'Szczecin'])->assertSuccessful();
        $this->assertSame(2, Provider::query()->where('source', 'google')->count(), 'tweede keer: bijgewerkt, niet dubbel');

        config(['fachora.google_places_key' => null]);
        $this->artisan('fachora:google-import', ['category' => 'hydraulik', 'city' => 'Szczecin'])->assertFailed();
        $this->assertFalse(app(JobService::class)->localImportAvailable(), 'zonder Google-sleutel en zonder CEIDG-token geen import op aanvraag');
    }

    public function test_with_only_register_companies_nearby_google_is_still_searched_first(): void
    {
        foreach (range(1, 6) as $i) {
            $this->registerProvider("Zakład Kowalski $i");
        }
        $this->fakeGoogle([$this->place('ChA', 'Hydro-Max Szczecin', 4.9, 120)]);
        $job = $this->confirmedJob();
        $this->assertSame('selecting', $job->status);
        $this->assertNotNull($job->provider_search_at, 'alleen registerbedrijven: eerst Google raadplegen, de klant wacht even');
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'choose');

        app(JobService::class)->runLocalSearches();
        $job->refresh();
        $this->assertNull($job->provider_search_at);
        $names = app(ProviderService::class)->choiceCandidates($job)->pluck('display_name')->all();
        $this->assertSame('Hydro-Max Szczecin', $names[0]);
        $this->assertCount(7, $names);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'choose' && $m->hasTo('anna@example.com'));
        $this->assertFalse(app(JobService::class)->needsQualitySearch($job), 'Google is geraadpleegd: niet nog eens');
    }

    public function test_an_older_selection_gets_the_google_search_when_the_customer_opens_the_page(): void
    {
        foreach (range(1, 6) as $i) {
            $this->registerProvider("Zakład Kowalski $i");
        }
        $this->fakeGoogle([$this->place('ChA', 'Hydro-Max Szczecin', 4.9, 120)]);
        $job = ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'selecting', 'confirmed_at' => now(), 'request_fee' => 0,
            'category' => 'hydraulik', 'title' => 'Kran', 'description' => 'Kran w kuchni cieknie, potrzebna wymiana.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '70-001', 'city' => 'Szczecin', 'lat' => 53.4285, 'lng' => 14.5528,
        ]);

        $this->get(route('fachora.job.show', $job->token))->assertOk()->assertInertia(fn ($page) => $page->where('choice.searching', true));
        $job->refresh();
        $this->assertNotNull($job->provider_search_at);
        $this->assertSame(1, $job->events()->where('kind', 'provider_research')->count());
        $this->get(route('fachora.job.show', $job->token))->assertOk();
        $this->assertSame(1, $job->events()->where('kind', 'provider_research')->count(), 'niet nog eens gestart');

        app(JobService::class)->runLocalSearches();
        $job->refresh();
        $this->assertNull($job->provider_search_at);
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'choose');
        $this->get(route('fachora.job.show', $job->token))->assertOk()->assertInertia(fn ($page) => $page
            ->where('choice.searching', false)->where('choice.candidates.0.name', 'Hydro-Max Szczecin')->where('choice.candidates.0.source', 'google'));
        $this->assertFalse(app(JobService::class)->ensureSearched($job->fresh()), 'Google is nu geraadpleegd: geen nieuwe zoektocht');
    }
}
