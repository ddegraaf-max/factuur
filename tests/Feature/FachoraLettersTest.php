<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\PingenClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 1.109: brieven per post via Pingen aan niet-geclaimde bedrijven die een klant koos — pilot per stad.
 * Zonder Pingen-gegevens alleen een proefdraai; met gegevens de drie API-stappen; nooit twee keer
 * dezelfde firma; alleen de gekozen stad; het logboek telt mee.
 */
class FachoraLettersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
    }

    private function provider(string $name, string $city, array $attrs = []): Provider
    {
        return Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => Str::slug($name) . '-' . Str::lower(Str::random(5)), 'status' => 'unclaimed',
            'source' => 'ceidg', 'address_line' => 'ul. Długa 1', 'postal_code' => '61-001', 'city' => $city, 'lat' => 52.4, 'lng' => 16.9,
            'optout_token' => bin2hex(random_bytes(32)),
        ]);
    }

    private function jobChoosing(array $providerIds): ServiceJob
    {
        return ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'selecting',
            'category' => 'hydraulik', 'title' => 'Kran', 'description' => 'Cieknący kran w kuchni.', 'urgency' => 'week', 'language' => 'pl',
            'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com', 'customer_locale' => 'pl',
            'postal_code' => '61-001', 'city' => 'Poznań', 'lat' => 52.4, 'lng' => 16.9, 'selected_providers' => $providerIds,
        ]);
    }

    private function fakePingen(): void
    {
        config(['services.pingen.client_id' => 'id', 'services.pingen.client_secret' => 'geheim', 'services.pingen.organisation' => 'org-1', 'fachora.letters.dry_run' => false]);
        Http::fake([
            'https://identity.pingen.com/auth/access-tokens' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'https://api.pingen.com/file-upload' => Http::response(['data' => ['attributes' => ['url' => 'https://upload.pingen.test/f1', 'url_signature' => 'sig']]]),
            'https://upload.pingen.test/f1' => Http::response('', 200),
            'https://api.pingen.com/organisations/org-1/letters' => Http::response(['data' => ['id' => 'ltr_123', 'attributes' => ['status' => 'validating']]], 201),
        ]);
    }

    public function test_without_pingen_it_only_shows_what_would_be_sent(): void
    {
        $chosen = $this->provider('Hydro Poznań', 'Poznań');
        $this->jobChoosing([$chosen->id]);

        $this->artisan('fachora:letters', ['--city' => 'Poznań'])
            ->expectsOutputToContain('[proef] Hydro Poznań')
            ->expectsOutputToContain('Pingen niet ingesteld')
            ->assertSuccessful();
        $this->assertNull($chosen->fresh()->letter_sent_at);
    }

    public function test_it_posts_the_letter_to_pingen_once_and_only_for_chosen_companies_in_the_city(): void
    {
        $this->fakePingen();
        $chosen = $this->provider('Hydro Poznań', 'Poznań');
        $elsewhere = $this->provider('Hydro Kraków', 'Kraków');
        $notChosen = $this->provider('Hydro Cichy', 'Poznań');
        $claimed = $this->provider('Hydro Verified', 'Poznań', ['status' => 'verified', 'claimed_at' => now()]);
        $this->jobChoosing([$chosen->id, $elsewhere->id, $claimed->id]);

        $this->artisan('fachora:letters', ['--city' => 'Poznań'])->expectsOutputToContain('1 van 1 brieven verstuurd')->assertSuccessful();

        $this->assertNotNull($chosen->fresh()->letter_sent_at);
        $this->assertSame('ltr_123', $chosen->fresh()->letter_ref);
        $this->assertNull($elsewhere->fresh()->letter_sent_at, 'andere stad');
        $this->assertNull($notChosen->fresh()->letter_sent_at, 'niet door een klant gekozen');
        $this->assertNull($claimed->fresh()->letter_sent_at, 'al geclaimd');

        // De drie API-stappen: token, uploadadres + PUT van de PDF, brief met auto_send en adresvenster links.
        Http::assertSent(fn ($r) => str_contains($r->url(), 'identity.pingen.com/auth/access-tokens') && $r['grant_type'] === 'client_credentials');
        Http::assertSent(fn ($r) => $r->url() === 'https://upload.pingen.test/f1' && $r->method() === 'PUT' && str_starts_with($r->body(), '%PDF'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/organisations/org-1/letters')
            && $r['data']['attributes']['auto_send'] === true
            && $r['data']['attributes']['address_position'] === 'left'
            && $r['data']['attributes']['file_url_signature'] === 'sig');

        $this->assertSame(1, DB::table('fachora_privacy_log')->where('action', 'letter_sent')->count());

        // Nog een run: niemand twee keer.
        $this->artisan('fachora:letters', ['--city' => 'Poznań'])->expectsOutputToContain('Geen bedrijven voor een brief in Poznań')->assertSuccessful();
        Http::assertSentCount(4);
    }

    public function test_the_scheduler_run_does_nothing_until_the_pilot_is_switched_on(): void
    {
        $this->fakePingen();
        config(['fachora.letters.enabled' => false]);
        $chosen = $this->provider('Hydro Poznań', 'Poznań');
        $this->jobChoosing([$chosen->id]);

        $this->artisan('fachora:letters')->assertSuccessful();
        $this->assertNull($chosen->fresh()->letter_sent_at);
        Http::assertNothingSent();

        config(['fachora.letters.enabled' => true, 'fachora.letters.city' => 'Poznań', 'fachora.letters.dry_run' => false, 'fachora.letters.per_day' => 10]);
        $this->artisan('fachora:letters')->assertSuccessful();
        $this->assertNotNull($chosen->fresh()->letter_sent_at);
    }

    public function test_the_postal_letter_pdf_renders_with_the_address_window(): void
    {
        $chosen = $this->provider('Hydro Poznań', 'Poznań');
        $pdf = app(\App\Services\Fachora\LetterService::class)->pdf(collect([$chosen]), chosen: true, postal: true);
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
