<?php

namespace Tests\Feature;

use App\Mail\SiteLeadMail;
use App\Models\AiUsageEvent;
use App\Models\CompanySite;
use App\Models\SiteLead;
use App\Services\Ai\StructuredClaude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Website per administratie: AI-tekst, bewerken, publiceren, publieke pagina en contactformulier. */
class CompanySiteTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function content(): array
    {
        return [
            'hero' => ['title' => 'Loodgieter in Utrecht', 'subtitle' => 'Snel en netjes geholpen.', 'cta' => 'Vraag een offerte aan'],
            'services' => [['title' => 'Lekkage verhelpen', 'description' => 'Vandaag nog.'], ['title' => 'CV-onderhoud', 'description' => 'Jaarlijks.']],
            'about' => ['title' => 'Over ons', 'text' => 'Wij zijn een familiebedrijf.'],
            'usps' => [['title' => 'Vaste prijs', 'text' => 'Vooraf duidelijk.']],
            'contact' => ['title' => 'Neem contact op', 'text' => 'Bel of mail gerust.'],
            'seo' => ['title' => 'Loodgieter Utrecht', 'description' => 'De loodgieter van Utrecht.'],
        ];
    }

    public function test_ai_writes_a_draft_and_usage_is_counted(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $content = $this->content();
        $this->app->instance(StructuredClaude::class, new class($content) extends StructuredClaude {
            public function __construct(private array $content) {}
            public function enabled(): bool { return true; }
            public function json(string $prompt, array $schema, int $maxTokens = 4000, string $effort = 'medium', array $blocks = [], string $label = 'AI'): array { return $this->content; }
        });

        $this->postJson(route('settings.site.generate'), ['what' => 'Loodgieter', 'audience' => 'huiseigenaren', 'why' => 'snel', 'tone' => 'nuchter en direct'])
            ->assertOk()->assertJsonPath('content.hero.title', 'Loodgieter in Utrecht')->assertJsonCount(2, 'content.services');

        $site = CompanySite::where('company_id', $user->company_id)->firstOrFail();
        $this->assertFalse($site->published, 'Concept: de gebruiker publiceert zelf.');
        $this->assertSame('Loodgieter', $site->answers['what']);
        $this->assertSame(1, AiUsageEvent::where('company_id', $user->company_id)->where('kind', 'site_generate')->count());
    }

    public function test_publishing_makes_the_site_public_with_a_working_contact_form(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $user->company->forceFill(['email' => 'info@example.com'])->save();
        $this->actingAs($user);

        $this->patch(route('settings.site.update'), ['published' => true, 'content' => $this->content()])->assertRedirect()->assertSessionHasNoErrors();
        $slug = $user->company->fresh()->public_slug;
        $this->assertNotEmpty($slug);

        $this->get("/s/{$slug}")->assertOk()->assertSee('Loodgieter in Utrecht')->assertSee('Lekkage verhelpen')->assertSee('Vaste prijs');

        $this->post("/s/{$slug}/contact", ['name' => 'Jan', 'email' => 'jan@example.com', 'phone' => '0612345678', 'message' => 'Kunnen jullie morgen komen?'])
            ->assertRedirect("/s/{$slug}#contact")->assertSessionHas('site_success');
        $this->assertSame(1, SiteLead::where('company_id', $user->company_id)->count());
        Mail::assertSent(SiteLeadMail::class, fn ($mail) => $mail->hasTo('info@example.com') && $mail->lead->name === 'Jan');

        // Honeypot ingevuld: bot, stil negeren.
        $this->post("/s/{$slug}/contact", ['name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'spam', 'website_url' => 'http://spam'])->assertRedirect();
        $this->assertSame(1, SiteLead::count());

        $this->get(route('settings.site'))->assertOk()->assertInertia(fn ($page) => $page->where('site.published', true)->where('leads.0.name', 'Jan'));
    }

    public function test_site_needs_a_title_to_go_online_and_stays_private_until_published(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $this->patch(route('settings.site.update'), ['published' => true, 'content' => ['hero' => ['title' => '']]])->assertSessionHasErrors('content');

        $this->patch(route('settings.site.update'), ['published' => false, 'content' => $this->content()])->assertSessionHasNoErrors();
        $slug = $user->company->fresh()->public_slug;
        $this->get("/s/{$slug}")->assertNotFound();
        $this->post("/s/{$slug}/contact", ['name' => 'Jan', 'email' => 'jan@example.com', 'message' => 'x'])->assertNotFound();
    }
}
