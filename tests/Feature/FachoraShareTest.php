<?php

namespace Tests\Feature;

use App\Mail\FachoraMail;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Fachora\JobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 1.98: de deelbare opdrachtpagina voor registerbedrijven (/z/{share}/{id}-{code}). De klant zet de link
 * in zijn eigen bericht; het bedrijf ziet de klus, leest dat het geen spam is en antwoordt met één klik.
 * Fachora stuurt het bedrijf zelf nog steeds niets.
 */
class FachoraShareTest extends TestCase
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
            'email' => "$slug@example.com", 'phone' => '+48 601 234 567', 'city' => 'Płock', 'postal_code' => '09-400', 'lat' => 52.5463, 'lng' => 19.7065,
            'pkd_main' => '43.22.Z', 'pkd_main_name' => 'Wykonywanie instalacji', 'pkd_checked_at' => now(),
        ]);
        $p->syncCategories(['kominiarz']);

        return $p->refresh();
    }

    private function openJob(array $providerIds): ServiceJob
    {
        return ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'open', 'confirmed_at' => now(), 'offered_at' => now(),
            'category' => 'kominiarz', 'title' => 'Schoorsteen vegen', 'description' => 'Schoorsteen vegen en controleren.', 'description_pl' => 'Przegląd i czyszczenie komina przed sezonem.',
            'ai' => ['title_pl' => 'Czyszczenie komina'], 'urgency' => 'week', 'language' => 'nl', 'languages' => ['pl', 'nl'],
            'customer_name' => 'Daniël de Graaf', 'customer_email' => 'daniel@example.com', 'customer_phone' => '+31 6 1234 5678', 'customer_locale' => 'en',
            'postal_code' => '09-400', 'city' => 'Płock', 'lat' => 52.5463, 'lng' => 19.7065, 'selected_providers' => $providerIds,
        ]);
    }

    public function test_the_customer_page_carries_a_share_link_per_company_and_the_company_page_explains_it_is_not_spam(): void
    {
        $p = $this->register('Kominiarz Nowak');
        $job = $this->openJob([$p->id]);

        $this->get(route('fachora.job.show', $job->token))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Fachora/Job')
                ->where('direct.0.id', $p->id)->where('direct.0.response', null)
                ->where('job.language_label_pl', 'polski, niderlandzki')->has('direct.0.share_url'));
        $url = app(JobService::class)->shareUrl($job->fresh(), $p);
        $this->assertStringContainsString('/z/' . $job->fresh()->share_token . '/' . $p->id . '-', $url);
        $this->assertStringNotContainsString($job->token, $url, 'de deel-link verraadt het klanttoken niet');

        $html = (string) $this->get($url)->assertOk()->getContent();
        $this->assertStringContainsString('noindex', $html);
        $this->assertStringContainsString('Czyszczenie komina', $html);
        $this->assertStringContainsString('Dla: Kominiarz Nowak', $html);
        $this->assertStringContainsString('Klient (Daniël)', $html);
        $this->assertStringContainsString('Fachora.pl nie wysłała tej wiadomości', $html);
        $this->assertStringContainsString('Przegląd i czyszczenie komina', $html);
        $this->assertStringContainsString('Tak, proszę o kontakt', $html);
        $this->assertStringContainsString('Usuń moją firmę z fachora.pl', $html);
        $this->assertStringContainsString('/rezygnacja/', $html, 'afmeldlink voor het registerbedrijf');

        $en = (string) $this->get($url . '?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('Why did you receive this message?', $en);

        // Openen is vastgelegd: de klant ziet het, met één regel in het logboek.
        $this->get(route('fachora.job.show', $job->token))->assertInertia(fn ($page) => $page->where('direct.0.response.kind', null)->has('direct.0.response.opened_at'));
        $this->assertSame(1, $job->events()->where('kind', 'direct_opened')->count());
        $this->assertSame(2, (int) $job->fresh()->direct_responses[(string) $p->id]['opened_count']);
    }

    public function test_a_wrong_code_or_unknown_token_gives_404_and_a_company_off_the_list_sees_the_job_is_no_longer_current(): void
    {
        $p = $this->register('Kominiarz Nowak');
        $job = $this->openJob([$p->id]);
        $share = $job->shareToken();
        $this->get("/z/{$share}/{$p->id}-0123456789")->assertNotFound();
        $this->get('/z/' . str_repeat('ab', 16) . "/{$p->id}-0123456789")->assertNotFound();
        $other = $this->register('Inna Firma');
        $code = app(JobService::class)->shareCode($job, $other->id);
        $this->get("/z/{$share}/{$other->id}-{$code}")->assertOk()->assertSee('nieaktualne')->assertDontSee('Tak, proszę o kontakt');
    }

    public function test_an_interested_company_reaches_the_customer_by_mail_and_on_the_job_page(): void
    {
        $p = $this->register('Kominiarz Nowak');
        $job = $this->openJob([$p->id]);
        $url = app(JobService::class)->shareUrl($job, $p);

        $this->post($url, ['kind' => 'interested', 'name' => '', 'phone' => '', 'email' => ''])->assertSessionHasErrors('phone');
        $this->post($url, ['kind' => 'interested', 'name' => 'Jan Nowak', 'phone' => '601 234 567', 'email' => 'jan@example.com', 'message' => "Mogę w przyszłym tygodniu.\nCena od 300 zł."])
            ->assertRedirect($url)->assertSessionHas('share_done', 'interested');
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'direct_response' && $m->audience === 'customer'
            && $m->hasTo('daniel@example.com') && $m->extra['interested'] === true && $m->extra['phone'] === '601 234 567');
        $c = (new FachoraMail('direct_response', 'customer', $job->fresh(), null, null, ['provider_name' => 'Kominiarz Nowak', 'interested' => true,
            'name' => 'Jan Nowak', 'phone' => '601 234 567', 'email' => 'jan@example.com', 'message' => 'Mogę w przyszłym tygodniu.']))->compose();
        $this->assertStringContainsString('Kominiarz Nowak', $c['subject']);
        $this->assertSame('601 234 567', $c['facts'][__('Telefoon')]);
        $this->assertSame('Mogę w przyszłym tygodniu.', $c['quote']);

        $this->get($url)->assertOk()->assertSee('Klient otrzymał');
        $this->get(route('fachora.job.show', $job->token))->assertInertia(fn ($page) => $page
            ->where('direct.0.response.kind', 'interested')->where('direct.0.response.name', 'Jan Nowak')->where('direct.0.response.email', 'jan@example.com'));
        $this->assertSame(1, $job->events()->where('kind', 'direct_interested')->count());
        Mail::assertNotSent(FachoraMail::class, fn (FachoraMail $m) => $m->audience === 'provider'); // het bedrijf krijgt van Fachora niets
    }

    public function test_declining_is_recorded_and_the_page_goes_inactive_once_the_customer_moves_on(): void
    {
        $p = $this->register('Kominiarz Nowak');
        $job = $this->openJob([$p->id]);
        $url = app(JobService::class)->shareUrl($job, $p);

        $this->post($url, ['kind' => 'declined'])->assertRedirect($url);
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'direct_response' && $m->extra['interested'] === false);
        $this->get(route('fachora.job.show', $job->token))->assertInertia(fn ($page) => $page->where('direct.0.response.kind', 'declined'));

        $job->forceFill(['status' => 'scheduled'])->save(); // de klant koos inmiddels een ander bedrijf
        $this->get($url)->assertOk()->assertSee('nieaktualne')->assertDontSee('Tak, proszę o kontakt');
        $this->post($url, ['kind' => 'interested', 'phone' => '601'])->assertSessionHasErrors('kind');
    }
}
