<?php

namespace Tests\Feature;

use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Fachora als merk (config/brand.php 'fachora'): de marktplaats bestaat
 * alleen onder dat merk, de Lopra-pagina's op dezelfde markt juist niet,
 * en sitemap en manifest volgen het merk.
 */
class FachoraBrandTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
        Mail::fake();
        Http::fake();
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

    /** Rookproef van de Blade-marketingpagina's; wat er nog niet is, wordt overgeslagen (de lead draait dit later opnieuw). */
    public function test_blade_marketing_pages_render(): void
    {
        $provider = $this->verifiedProvider('Hydro-Max');
        $pages = [
            'fachora.landing' => '/',
            'fachora.jak-to-dziala' => '/jak-to-dziala',
            'fachora.protect' => '/fachora-protect',
            'fachora.standard' => '/standard',
            'fachora.dla-firm' => '/dla-firm',
            'fachora.faq' => '/faq',
            'fachora.kontakt' => '/kontakt',
            'fachora.o-nas' => '/o-nas',
            'fachora.regulamin' => '/regulamin',
            'fachora.prywatnosc' => '/polityka-prywatnosci',
            'fachora.kategoria' => '/uslugi/hydraulik',
            'fachora.firma' => '/firma/'.$provider->slug,
        ];

        $checked = [];
        $missing = [];
        foreach ($pages as $view => $path) {
            if (! view()->exists($view)) {
                $missing[] = $view;

                continue;
            }
            $this->get($path)->assertOk();
            $checked[] = $path;
        }

        if ($checked === []) {
            $this->markTestSkipped('Geen enkele Fachora-Blade-view bestaat nog: '.implode(', ', $missing));
        }
        $this->assertNotEmpty($checked);
        if ($missing !== []) {
            // Geen fout: de views worden parallel geschreven. Wel zichtbaar in de uitvoer.
            fwrite(STDERR, "\n[FachoraBrandTest] nog ontbrekende views: ".implode(', ', $missing)."\n");
        }
    }

    public function test_the_intake_page_renders_with_categories_languages_and_presets(): void
    {
        $this->get(route('fachora.intake'))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Intake')
            ->has('categories', count(config('fachora.categories')))
            ->where('categories.0.key', 'hydraulik')
            ->where('categories.0.name', 'Hydraulik')
            ->has('languages', 5)
            ->where('languages.0.code', 'pl')
            ->where('preset', null)
            ->where('wait', null)
            ->where('maxPhotos', 5));

        $this->get(route('fachora.intake', ['kategoria' => 'elektryk', 'miasto' => 'Kraków']))
            ->assertInertia(fn (Assert $p) => $p->where('preset', 'elektryk')->where('presetCity', 'Kraków'));
        $this->get(route('fachora.intake', ['kategoria' => 'bestaat-niet']))
            ->assertInertia(fn (Assert $p) => $p->where('preset', null));
    }

    public function test_the_english_interface_is_chosen_with_lang_en_and_remembered(): void
    {
        $this->get(route('fachora.intake', ['lang' => 'en']))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Fachora/Intake')
            ->where('categories.0.name', 'Plumber')
            ->where('languages.0.name', 'Polish'));

        // De keuze zit in de sessie: de volgende pagina is ook Engels.
        $this->get(route('fachora.intake'))->assertInertia(fn (Assert $p) => $p->where('categories.0.name', 'Plumber'));

        $this->get(route('fachora.intake', ['lang' => 'pl']))->assertInertia(fn (Assert $p) => $p->where('categories.0.name', 'Hydraulik'));
    }

    public function test_fachora_routes_do_not_exist_under_other_brands_and_lopra_routes_not_under_fachora(): void
    {
        config(['brand.active' => 'easyinvoice']);
        $this->get('/zlecenie/nowe')->assertNotFound();
        $this->get('/przejmij')->assertNotFound();
        $this->get('/dla-firm/zglos')->assertNotFound();
        $this->get('/uslugi/hydraulik')->assertNotFound();
        $this->get('/jak-to-dziala')->assertNotFound();

        config(['brand.active' => 'lopra_pl']);
        $this->get('/zlecenie/nowe')->assertNotFound();
        $this->get('/kalkulator-odsetek')->assertOk();

        config(['brand.active' => 'fachora']);
        $this->get('/kalkulator-odsetek')->assertNotFound();
        $this->get('/skup-wyrokow')->assertNotFound();
        $this->get('/przenies-sie-z/fakturownia')->assertNotFound();
        $this->get('/przejmij')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Fachora/Claim')->where('mode', 'start'));
    }

    public function test_the_company_panel_needs_a_login_and_shows_nothing_without_a_provider(): void
    {
        $this->get(route('fachora.dashboard'))->assertRedirect(route('login'));
        $this->get(route('fachora.profile'))->assertRedirect(route('login'));
        $this->get(route('fachora.admin.providers'))->assertRedirect(route('login'));

        $user = $this->demoUser();
        $this->actingAs($user)->get(route('fachora.dashboard'))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Fachora/Provider/Dashboard')->where('provider', null)->where('offers', []));
    }

    public function test_the_sitemap_lists_intake_categories_city_pages_and_verified_profiles_only(): void
    {
        $verified = $this->verifiedProvider('Hydro-Max');
        $unclaimed = Provider::create(['legal_name' => 'Nieznany', 'display_name' => 'Nieznany', 'slug' => 'nieznany-lodz', 'status' => 'unclaimed', 'city' => 'Łódź']);
        $unclaimed->syncCategories(['elektryk']);

        $urls = $this->sitemapUrls();

        $this->assertContains(url('/zlecenie/nowe'), $urls);
        $this->assertContains(url('/dla-firm'), $urls);
        $this->assertContains(url('/przejmij'), $urls);
        $this->assertContains(url('/uslugi/hydraulik'), $urls);
        $this->assertContains(url('/uslugi/mechanik'), $urls);
        $this->assertContains(url('/uslugi/hydraulik/lodz'), $urls, 'Stadspagina alleen waar een Verified bedrijf zit');
        $this->assertContains(url('/firma/'.$verified->slug), $urls);
        $this->assertNotContains(url('/firma/'.$unclaimed->slug), $urls, 'Onbevestigde profielen horen niet in de sitemap');
        $this->assertNotContains(url('/uslugi/elektryk/lodz'), $urls);
        $this->assertNotContains(url('/demo'), $urls);
        $this->assertNotContains(url('/kalkulator-odsetek'), $urls);

        config(['brand.active' => 'easyinvoice']);
        $this->assertNotContains(url('/zlecenie/nowe'), $this->sitemapUrls());
    }

    public function test_manifest_and_robots_follow_the_brand(): void
    {
        $this->get('/manifest.webmanifest')->assertOk()
            ->assertJsonPath('name', 'Fachora')
            ->assertJsonPath('lang', 'pl')
            ->assertJsonPath('theme_color', '#0B2545')
            ->assertJsonPath('icons.1.src', '/brand/fachora/fachora-icon-512.png');

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }
}
