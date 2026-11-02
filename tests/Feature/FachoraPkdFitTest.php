<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\CeidgClient;
use App\Services\Fachora\JobService;
use App\Services\Fachora\ProviderService;
use App\Support\Fachora;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Hoofdactiviteit uit CEIDG (1.96): alleen bedrijven waarvan de hoofd-PKD vakwerk is komen
 * in de keuzelijst (passend bij de categorie eerst, bijcode daarna, met label); bedrijven
 * zonder vakwerk als hoofdactiviteit worden niet geïmporteerd en, als ze er al stonden,
 * door fachora:pkd-check verwijderd met een regel in het verantwoordingslog.
 */
class FachoraPkdFitTest extends TestCase
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
            'fachora.ceidg_token' => 'jwt', 'fachora.local_import' => true,
        ]);
        Cache::flush();
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-11-02 10:00'));
    }

    public function test_every_category_code_counts_as_trade_work_and_unrelated_codes_do_not(): void
    {
        foreach ((array) config('fachora.categories') as $key => $cat) {
            foreach ((array) $cat['pkd'] as $code) {
                $this->assertTrue(Fachora::isTradePkd($code), "{$key}: {$code} hoort vakwerk te zijn");
            }
        }
        $this->assertTrue(Fachora::isTradePkd('81.22.Z'), 'kominiarstwo');
        $this->assertTrue(Fachora::isTradePkd('4322Z'));
        $this->assertFalse(Fachora::isTradePkd('68.31.Z'), 'vastgoedbemiddeling');
        $this->assertFalse(Fachora::isTradePkd('73.11.Z'), 'reclamebureau');
        $this->assertFalse(Fachora::isTradePkd(null));
        $this->assertSame('4322Z', Fachora::normalisePkd('43.22.Z'));
    }

    private function register(string $name, ?string $main, array $attrs = []): Provider
    {
        $slug = Str::slug($name) . '-' . Str::lower(Str::random(4));
        $p = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed', 'source' => 'ceidg',
            'email' => "$slug@example.com", 'city' => 'Płock', 'postal_code' => '09-400', 'lat' => 52.5463, 'lng' => 19.7065,
            'pkd_main' => $main, 'pkd_main_name' => $main ? "Działalność $main" : null, 'pkd_checked_at' => $main ? now() : null,
        ]);
        $p->syncCategories(['kominiarz']);

        return $p->refresh();
    }

    private function job(): ServiceJob
    {
        return ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'selecting', 'confirmed_at' => now(),
            'category' => 'kominiarz', 'title' => 'Komin', 'description' => 'Przegląd i czyszczenie komina przed sezonem.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '09-400', 'city' => 'Płock', 'lat' => 52.5463, 'lng' => 19.7065,
        ]);
    }

    public function test_the_choice_list_puts_the_main_activity_first_and_leaves_unrelated_companies_out(): void
    {
        $sweep = $this->register('Kominiarz Nowak', '81.22.Z', ['rating_avg' => 3.0]);
        $builder = $this->register('Budowlanka Kowalski', '43.99.Z', ['rating_avg' => 5.0]);   // vakwerk, maar de klus is een bijcode
        $unknown = $this->register('Stary import', null, ['rating_avg' => 5.0]);
        $estate = $this->register('Pasja Nieruchomości', '68.31.Z', ['rating_avg' => 5.0]);     // geen vakwerk

        $this->assertSame('main', $sweep->pkdFit('kominiarz'));
        $this->assertSame('secondary', $builder->pkdFit('kominiarz'));
        $this->assertSame('unknown', $unknown->pkdFit('kominiarz'));
        $this->assertSame('unrelated', $estate->pkdFit('kominiarz'));

        $ids = app(ProviderService::class)->choiceCandidates($this->job())->pluck('id')->all();
        $this->assertSame([$sweep->id, $builder->id, $unknown->id], $ids, 'passend, dan bijcode, dan onbekend; vastgoed valt weg');

        $job = $this->job();
        $this->get(route('fachora.job.show', $job->token))->assertOk()
            ->assertInertia(fn ($p) => $p->where('choice.candidates.0.pkd_fit', 'main')
                ->where('choice.candidates.0.pkd_main_name', 'Działalność 81.22.Z')
                ->where('choice.candidates.1.pkd_fit', 'secondary')
                ->where('choice.candidates.2.pkd_fit', 'unknown'));
    }

    public function test_the_import_skips_companies_whose_main_activity_is_not_trade_work(): void
    {
        $firm = fn (string $id, string $nip) => [
            'id' => $id, 'nazwa' => "Firma $id", 'dataRozpoczecia' => '2018-03-01', 'status' => 'AKTYWNY',
            'adresDzialalnosci' => ['miasto' => 'Płock', 'kod' => '09-400', 'ulica' => 'ul. Tumska', 'budynek' => '5'],
            'wlasciciel' => ['imie' => 'Jan', 'nazwisko' => "Kowalski $id", 'nip' => $nip],
        ];
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response(['address' => ['county' => 'powiat płocki']], 200),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '52.5463', 'lon' => '19.7065']], 200),
            'dane.biznes.gov.pl/api/ceidg/v3/firmy*' => Http::response(['firmy' => [$firm('komin', '5260250274'), $firm('estate', '5651043094')], 'count' => 2], 200),
            'dane.biznes.gov.pl/api/ceidg/v3/firma/komin' => Http::response(['firma' => [['email' => 'biuro@komin.pl', 'pkdGlowny' => ['kod' => '81.22.Z', 'nazwa' => 'Specjalistyczne sprzątanie'], 'pkd' => [['kod' => '81.22.Z']]]]], 200),
            'dane.biznes.gov.pl/api/ceidg/v3/firma/estate' => Http::response(['firma' => [['email' => 'biuro@estate.pl', 'pkdGlowny' => ['kod' => '68.31.Z', 'nazwa' => 'Pośrednictwo nieruchomości'], 'pkd' => [['kod' => '68.31.Z'], ['kod' => '81.22.Z']]]]], 200),
            '*' => Http::response([], 200),
        ]);
        $job = ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => 'kominiarz', 'title' => 'Komin', 'description' => 'Przegląd i czyszczenie komina przed sezonem.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '09-400', 'city' => 'Płock', 'lat' => 52.5463, 'lng' => 19.7065,
        ]);
        app(JobService::class)->confirm($job, request());
        $this->artisan('fachora:local-search')->assertSuccessful();

        $sweep = Provider::query()->where('nip', '5260250274')->first();
        $this->assertNotNull($sweep, 'de schoorsteenveger is geïmporteerd');
        $this->assertSame('81.22.Z', $sweep->pkd_main);
        $this->assertSame('Specjalistyczne sprzątanie', $sweep->pkd_main_name);
        $this->assertSame('komin', $sweep->source_id);
        $this->assertNotNull($sweep->pkd_checked_at);
        $this->assertNull(Provider::query()->where('nip', '5651043094')->first(), 'vastgoed met een bouwcode als bijcode wordt niet geïmporteerd');
    }

    public function test_pkd_check_fetches_the_main_activity_for_old_records_and_removes_unrelated_ones_with_a_log(): void
    {
        $old = $this->register('Stary bez PKD', null, ['nip' => '5651043094', 'source_id' => null]);
        $kept = $this->register('Komin OK', '81.22.Z');
        $estate = $this->register('Nieruchomości', '68.31.Z');
        $chosen = $this->register('Wybrany przez klienta', '68.31.Z');
        $this->job()->forceFill(['selected_providers' => [$chosen->id]])->save(); // keuze van vóór 1.96

        Http::fake([
            'dane.biznes.gov.pl/api/ceidg/v3/firmy*' => Http::response(['firmy' => [['id' => 'old-1', 'nazwa' => 'Stary bez PKD']], 'count' => 1], 200),
            'dane.biznes.gov.pl/api/ceidg/v3/firma/old-1' => Http::response(['firma' => [['email' => 'stary@example.com', 'pkdGlowny' => ['kod' => '73.11.Z', 'nazwa' => 'Agencje reklamowe'], 'pkd' => [['kod' => '73.11.Z']]]]], 200),
            '*' => Http::response([], 200),
        ]);

        $this->artisan('fachora:pkd-check --dry-run')->expectsOutputToContain('zou verwijderen: 2')->assertSuccessful();
        $this->assertNotNull(Provider::query()->find($estate->id), 'dry-run verwijdert niets');

        $this->artisan('fachora:pkd-check')->expectsOutputToContain('verwijderd (geen vakwerk als hoofdactiviteit): 2')->assertSuccessful();
        $this->assertNull(Provider::query()->find($old->id), 'via NIP nagevraagd: reclamebureau, dus weg');
        $this->assertNull(Provider::query()->find($estate->id));
        $this->assertNotNull(Provider::query()->find($kept->id));
        $this->assertNotNull(Provider::query()->find($chosen->id), 'door een klant gekozen: blijft staan voor zijn contact');
        $this->assertDatabaseHas('fachora_privacy_log', ['action' => 'purge_unrelated', 'count' => 2]);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/firmy') && str_contains($r->url(), 'nip=5651043094'));
    }
}
