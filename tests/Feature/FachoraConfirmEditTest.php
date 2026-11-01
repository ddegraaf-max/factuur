<?php

namespace Tests\Feature;

use App\Models\ServiceJob;
use App\Services\Fachora\CeidgClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Bevestigingspagina: terug in de aanvraag om aan te passen, advies vóór de
 * bevestiging, en het zoeken in het register reikt tot steden in de straal
 * (Szczawin Kościelny → ook Płock).
 */
class FachoraConfirmEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false,
            'services.anthropic.key' => null, 'fachora.customer_choice' => true,
        ]);
        Cache::flush();
        Mail::fake();
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response(['address' => ['county' => 'powiat gostyniński']], 200),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '52.4410', 'lon' => '19.5300']], 200),
            '*' => Http::response([], 200),
        ]);
    }

    private function pendingJob(array $attrs = []): ServiceJob
    {
        return ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
            'category' => array_key_first(config('fachora.categories')), 'title' => 'Kominiarz', 'description' => 'Ik wil een controle van de openhaard.',
            'urgency' => 'week', 'language' => 'en', 'customer_name' => 'Daniël', 'customer_email' => 'd@example.com',
            'customer_locale' => 'pl', 'postal_code' => '09-550', 'city' => 'Szczawin Kościelny', 'lat' => 52.4410, 'lng' => 19.5300,
        ]);
    }

    public function test_the_confirm_page_gives_advice_and_mentions_plock_in_the_radius(): void
    {
        $job = $this->pendingJob();
        $this->get(route('fachora.job.confirm.show', $job->token))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Fachora/Confirm')
                ->where('advice.radius', 50)
                ->where('advice.towns.0.name', 'Płock')
                ->where('advice.tips.0.key', 'language')
                ->where('advice.tips.1.key', 'description')
                ->has('categories')->has('languages'));
    }

    public function test_the_customer_can_go_back_and_change_the_request_before_confirming(): void
    {
        $job = $this->pendingJob();
        $this->post(route('fachora.job.confirm.update', $job->token), [
            'category' => $job->category, 'title' => '', 'description' => 'Przegląd kominka i czyszczenie komina, ewentualnie nowy kominek na piętrze. Coroczny przegląd.',
            'urgency' => 'month', 'language' => 'pl', 'postal_code' => '09-550', 'city' => 'Szczawin Kościelny',
        ])->assertRedirect($job->confirmUrl());

        $job->refresh();
        $this->assertSame('pending', $job->status);
        $this->assertSame('pl', $job->language);
        $this->assertSame('month', $job->urgency);
        $this->assertStringStartsWith('Przegląd kominka', $job->description);
        $this->assertSame([], app(\App\Services\Fachora\JobService::class)->advice($job)['tips']);
    }

    public function test_a_confirmed_request_can_no_longer_be_changed(): void
    {
        $job = $this->pendingJob(['status' => 'selecting']);
        $this->post(route('fachora.job.confirm.update', $job->token), [
            'category' => $job->category, 'description' => 'Iets heel anders dan eerst gevraagd.', 'urgency' => 'now', 'language' => 'pl', 'city' => 'Gdzieś',
        ])->assertRedirect($job->url());
        $this->assertSame('Szczawin Kościelny', $job->fresh()->city);
    }

    public function test_the_register_search_also_covers_nearby_towns_and_counties(): void
    {
        $areas = app(CeidgClient::class)->searchAreas('Szczawin Kościelny', [52.4410, 19.5300], 50);
        $this->assertSame(['miasto' => 'Szczawin Kościelny'], $areas[0]);
        $this->assertSame(['miasto' => 'Płock'], $areas[1]);
        $this->assertContains(['powiat' => 'gostyniński'], $areas);
        $this->assertNotContains(['miasto' => 'Warszawa'], $areas, 'Warszawa ligt verder dan 50 km');
    }
}
