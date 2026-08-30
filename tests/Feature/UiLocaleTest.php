<?php

namespace Tests\Feature;

use App\Support\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Interfacetaal per markt: Nederland alleen nl, Lopra Polska pl én en.
 * De keuze komt uit ?lang=…, de gebruiker of de sessie en wordt onthouden;
 * merkteksten en marketingpagina's volgen mee (Brand i18n, Market::view()).
 */
class UiLocaleTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_dutch_market_has_a_single_interface_language(): void
    {
        $this->assertSame(['nl'], Market::uiLocales());
        $this->assertFalse(Market::isUiLocale('en'));

        $this->get('/lang/en')->assertNotFound();
        $this->get('/?lang=en')->assertOk()->assertSee('<html lang="nl"', false);
    }

    public function test_polish_market_offers_polish_and_english(): void
    {
        config(['brand.active' => 'lopra_pl']);

        $this->assertSame(['pl', 'en'], Market::uiLocales());
        $this->assertSame(['pl', 'en'], Market::forClient()['locales']);

        // Standaard Pools, met de PL/EN-schakelaar in de navigatie.
        $this->get('/')->assertOk()
            ->assertSee('<html lang="pl"', false)
            ->assertSee('class="nav-lang-link is-active" hreflang="pl"', false)
            ->assertSee('Zaloguj się');

        // ?lang=en schakelt om en wordt in de sessie onthouden.
        $this->get('/?lang=en')->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('data-market-locale="pl"', false)
            ->assertSee('class="nav-lang-link is-active" hreflang="en"', false)
            ->assertSee('Log in')
            ->assertSessionHas('ui_locale', 'en');

        // Sessievoorkeur blijft gelden zonder ?lang.
        $this->withSession(['ui_locale' => 'en'])->get('/faq')->assertOk()->assertSee('<html lang="en"', false);

        // Een onbekende taal wordt genegeerd (sessie leeg → markttaal).
        $this->flushSession();
        $this->get('/?lang=de')->assertOk()->assertSee('<html lang="pl"', false);
        $this->get('/lang/de')->assertNotFound();
    }

    public function test_language_switch_is_remembered_for_the_user(): void
    {
        config(['brand.active' => 'lopra_pl']);

        $user = $this->demoUser();
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk()->assertSee('<html lang="pl"', false);

        $this->from(route('dashboard'))->get(route('lang', 'en'))->assertRedirect(route('dashboard'));
        $this->assertSame('en', $user->fresh()->locale);

        // Volgende verzoek: voorkeur van de gebruiker wint van de markttaal, ook zonder sessie.
        $this->flushSession();
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('&quot;locale&quot;:&quot;en&quot;', false);

        // Terug naar Pools.
        $this->from(route('dashboard'))->get(route('lang', 'pl'))->assertRedirect(route('dashboard'));
        $this->assertSame('pl', $user->fresh()->locale);
    }

    public function test_language_switch_never_redirects_off_site(): void
    {
        config(['brand.active' => 'lopra_pl']);

        $this->withHeaders(['Referer' => 'https://evil.example/phish'])
            ->get(route('lang', 'en'))
            ->assertRedirect('/');
    }

    public function test_brand_texts_follow_the_interface_language(): void
    {
        config(['brand.active' => 'lopra_pl']);

        app()->setLocale('pl');
        $this->assertSame('Cała Twoja firma w jednym miejscu', brand('auth_title'));

        app()->setLocale('en');
        $this->assertSame('Your whole business in one place', brand('auth_title'));
        $this->assertSame('Lopra', brand('name'));           // geen Engelse variant → Poolse/gewone waarde
        $this->assertSame('pl', brand('market'));            // markt wisselt nooit mee
        $this->assertSame('pl', Market::locale());           // documenttaal blijft de markttaal

        // Login-pagina in het Engels: Engelse SEO-titel en Engelse merkteksten in de Inertia-props.
        $this->withSession(['ui_locale' => 'en'])->get(route('login'))->assertOk()
            ->assertSee('Log in to Lopra', false)
            ->assertSee('Your whole business in one place', false);
    }

    public function test_english_marketing_views_are_used_when_they_exist(): void
    {
        config(['brand.active' => 'lopra_pl']);

        app()->setLocale('pl');
        $this->assertSame('lopra-pl.landing', Market::view('lopra-pl.landing'));

        app()->setLocale('en');
        $this->assertSame('marketing.demo', Market::view('marketing.demo')); // geen Engelse tegenhanger → origineel

        if (view()->exists('lopra-pl.en.landing')) {
            $this->assertSame('lopra-pl.en.landing', Market::view('lopra-pl.landing'));
            $this->withSession(['ui_locale' => 'en'])->get('/')->assertOk()->assertDontSee('Zacznij za darmo');
        }
    }

    public function test_english_pages_render_for_a_polish_owner(): void
    {
        config(['brand.active' => 'lopra_pl']);

        // Publieke pagina's in het Engels (Engelse tegenhanger of Pools met Engelse toelichting).
        foreach (['/', '/demo', '/faq', '/kontakt', '/o-nas', '/regulamin', '/polityka-prywatnosci', '/kalkulator-odsetek', '/przenies-sie-z/fakturownia', '/login', '/register'] as $path) {
            $this->withSession(['ui_locale' => 'en'])->get($path)->assertOk()->assertSee('<html lang="en"', false);
        }
        $this->withSession(['ui_locale' => 'en'])->get('/regulamin')->assertSee('legally binding version');

        // Demo starten vanaf de Engelse site: Engelse welkomstmelding en Engelse app.
        $this->withSession(['ui_locale' => 'en'])->post('/demo')->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk()->assertSee('<html lang="en"', false)->assertSee('Welcome to the demo!');
        auth()->logout();

        $user = $this->demoUser();
        $user->forceFill(['locale' => 'en'])->save();
        $this->actingAs($user);

        foreach (['dashboard', 'invoices.index', 'invoices.create', 'customers.index', 'vat.index', 'incasso.index', 'settings.company', 'billing.show'] as $name) {
            $this->get(route($name))->assertOk()->assertSee('<html lang="en"', false);
        }
    }
}
