<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\JobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/** 1.99.2: de klant sluit een registerbedrijf af als "geen reactie"; heropenen kan, en een laat antwoord opent het kaartje weer. */
class FachoraNoReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'fachora.email_unregistered' => false]);
        Mail::fake();
    }

    private function register(string $name): Provider
    {
        $slug = Str::slug($name) . '-' . Str::lower(Str::random(4));
        $p = Provider::create([
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed', 'source' => 'ceidg',
            'email' => "$slug@example.com", 'city' => 'Płock', 'postal_code' => '09-400', 'lat' => 52.5463, 'lng' => 19.7065, 'pkd_main' => '43.22.Z', 'pkd_checked_at' => now(),
        ]);
        $p->syncCategories(['kominiarz']);

        return $p->refresh();
    }

    public function test_closing_collapses_the_company_reopening_restores_it_and_a_late_reply_reopens_it_by_itself(): void
    {
        $a = $this->register('Firma A');
        $b = $this->register('Firma B');
        $job = ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'open', 'confirmed_at' => now(), 'offered_at' => now(),
            'category' => 'kominiarz', 'title' => 'Komin', 'description' => 'Czyszczenie komina.', 'urgency' => 'week', 'language' => 'pl',
            'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com', 'customer_locale' => 'pl', 'postal_code' => '09-400', 'city' => 'Płock', 'lat' => 52.5463, 'lng' => 19.7065,
            'selected_providers' => [$a->id, $b->id],
        ]);
        $show = fn () => $this->get(route('fachora.job.show', $job->token))->assertOk();

        $this->post(route('fachora.job.direct.close', [$job->token, $a->id]))->assertRedirect();
        $show()->assertInertia(fn ($page) => $page->has('direct.0.response.closed_at')->where('direct.1.response', null));
        $this->assertSame(1, $job->events()->where('kind', 'direct_closed')->count());

        $this->post(route('fachora.job.direct.close', [$job->token, $a->id]), ['undo' => 1])->assertRedirect();
        $show()->assertInertia(fn ($page) => $page->where('direct.0.response.closed_at', null));
        $this->assertSame(1, $job->events()->where('kind', 'direct_reopened')->count());

        // Dicht, en dan antwoordt het bedrijf alsnog via de deelpagina: het kaartje gaat vanzelf weer open.
        $this->post(route('fachora.job.direct.close', [$job->token, $a->id]));
        $this->post(app(JobService::class)->shareUrl($job->fresh(), $a), ['kind' => 'interested', 'phone' => '601 234 567'])->assertSessionHasNoErrors();
        $show()->assertInertia(fn ($page) => $page->where('direct.0.response.closed_at', null)->where('direct.0.response.kind', 'interested'));

        // Een bedrijf dat niet op de lijst staat, kan niet worden afgesloten.
        $other = $this->register('Firma C');
        $this->post(route('fachora.job.direct.close', [$job->token, $other->id]))->assertRedirect();
        $this->assertArrayNotHasKey((string) $other->id, (array) $job->fresh()->direct_responses);
    }
}
