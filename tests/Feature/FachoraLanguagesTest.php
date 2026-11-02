<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\JobService;
use App\Services\Fachora\Presenter;
use App\Services\Fachora\ProviderService;
use App\Support\Fachora;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Meerdere talen per aanvraag (1.95): Pools staat altijd aan, de overige talen zijn een
 * voorkeur. 'language' blijft de voorkeurstaal (eerste taal naast het Pools) voor bestaande
 * schermen; 'languages' is de volledige lijst. Bedrijven die een extra taal spreken komen in
 * de keuzelijst vooraan; bij het aanbieden gelden ze als eerste ronde (zie FachoraMatchingTest).
 */
class FachoraLanguagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
        Http::fake(['*' => Http::response([], 200)]);
        Mail::fake();
    }

    private function verifiedProvider(string $name, array $attrs = []): Provider
    {
        $slug = Str::slug($name) . '-' . Str::lower(Str::random(5));
        $provider = Provider::create($attrs + [
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'verified',
            'email' => $slug . '@example.com', 'city' => 'Łódź', 'postal_code' => '90-001',
            'lat' => 51.7592, 'lng' => 19.4560, 'radius_km' => 25, 'languages' => ['pl' => 'native'],
            'claimed_at' => now()->subMonth(), 'consent_at' => now()->subMonth(), 'consent_source' => 'claim',
            'verified_at' => now()->subDays(100), 'optout_token' => bin2hex(random_bytes(32)), 'rating_avg' => 4.5,
        ]);
        $provider->syncCategories(['hydraulik']);

        return $provider->refresh();
    }

    private function job(array $attrs = []): ServiceJob
    {
        return ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'selecting', 'confirmed_at' => now(),
            'category' => 'hydraulik', 'title' => 'Wyciek pod zlewem', 'description' => 'Kapie pod zlewem w kuchni, potrzebna szybka naprawa.',
            'urgency' => 'week', 'language' => 'pl', 'customer_name' => 'Anna Kowalska', 'customer_email' => 'anna@example.com',
            'customer_locale' => 'pl', 'postal_code' => '90-001', 'city' => 'Łódź', 'lat' => 51.7592, 'lng' => 19.4560,
        ]);
    }

    public function test_polish_is_always_first_and_unknown_codes_fall_away(): void
    {
        $this->assertSame(['pl', 'en', 'de'], Fachora::languageCodes(['languages' => ['en', 'xx', 'de', 'en']]));
        $this->assertSame(['pl', 'de'], Fachora::languageCodes(['language' => 'de']), 'het oude losse veld blijft werken');
        $this->assertSame(['pl'], Fachora::languageCodes(['languages' => ['pl']]));
        $this->assertSame(['pl'], Fachora::languageCodes([]));
        $this->assertSame('en', Fachora::preferredLanguage(['pl', 'en', 'de']));
        $this->assertSame('pl', Fachora::preferredLanguage(['pl']));

        $job = $this->job(['language' => 'en', 'languages' => ['pl', 'en', 'de']]);
        $this->assertSame(['pl', 'en', 'de'], $job->languageCodes());
        $this->assertSame(['en', 'de'], $job->extraLanguages());
        $old = $this->job(['language' => 'nl']); // opdracht van vóór 1.95
        $this->assertSame(['pl', 'nl'], $old->languageCodes());

        $label = app(Presenter::class)->job($job, 'customer')['language_label'];
        $this->assertStringContainsString('Polski', $label);
        $this->assertStringContainsString('Angielski', $label);
        $this->assertStringContainsString('Niemiecki', $label);
    }

    public function test_the_intake_stores_all_languages_and_derives_the_preferred_one(): void
    {
        $form = [
            'category' => 'hydraulik', 'description' => 'The tap under the kitchen sink is leaking, needs a quick repair.',
            'urgency' => 'week', 'languages' => ['en', 'nl'], 'customer_name' => 'John Doe', 'customer_email' => 'john@example.com',
            'customer_phone' => '600 100 200', 'postal_code' => '90-001', 'city' => 'Łódź',
        ];
        $this->post(route('fachora.intake.store'), $form)->assertRedirect();
        $job = ServiceJob::query()->latest('id')->firstOrFail();
        $this->assertSame(['pl', 'en', 'nl'], $job->languages, 'Pools komt er altijd bij, vooraan');
        $this->assertSame('en', $job->language, 'de eerste taal naast het Pools is de voorkeurstaal');

        $this->post(route('fachora.intake.store'), ['languages' => ['xx']] + $form)->assertSessionHasErrors(['languages.0']);
        $this->post(route('fachora.intake.store'), ['languages' => null, 'language' => 'de', 'customer_email' => 'other@example.com'] + $form)->assertRedirect();
        $this->assertSame(['pl', 'de'], ServiceJob::query()->latest('id')->firstOrFail()->languages);
    }

    public function test_the_customer_can_change_the_languages_before_confirming(): void
    {
        $job = $this->job(['status' => 'pending', 'confirmed_at' => null, 'language' => 'en', 'languages' => ['pl', 'en']]);
        $this->post(route('fachora.job.confirm.update', $job->token), [
            'category' => 'hydraulik', 'title' => '', 'description' => $job->description, 'urgency' => 'week',
            'languages' => ['ua'], 'postal_code' => '90-001', 'city' => 'Łódź',
        ])->assertRedirect($job->confirmUrl());
        $job->refresh();
        $this->assertSame(['pl', 'ua'], $job->languages);
        $this->assertSame('ua', $job->language);
        $this->assertSame([], array_filter(app(JobService::class)->advice($job)['tips'], fn ($t) => $t['key'] === 'language'), 'geen taaltip meer: Pools staat altijd aan');
    }

    public function test_companies_that_speak_an_extra_language_come_first_in_the_choice_list(): void
    {
        $polishOnly = $this->verifiedProvider('Tylko Polski', ['rating_avg' => 4.9]);
        $english = $this->verifiedProvider('English Speaker', ['rating_avg' => 4.2, 'languages' => ['pl' => 'native', 'en' => 'b2']]);
        $service = app(ProviderService::class);

        $this->assertSame([$polishOnly->id, $english->id], $service->choiceCandidates($this->job())->pluck('id')->all(), 'alleen Pools: op beoordeling');
        $this->assertSame([$english->id, $polishOnly->id], $service->choiceCandidates($this->job(['language' => 'en', 'languages' => ['pl', 'en']]))->pluck('id')->all(), 'Engels als extra taal: de Engelssprekende vooraan');
        $this->assertCount(2, $service->choiceCandidates($this->job(['language' => 'nl', 'languages' => ['pl', 'nl']])), 'een taal die niemand spreekt sluit niemand uit');
    }
}
