<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Huisstijl 1.106.0 van EasyInvoice (ontwerp van Daniël): donkere hero met rood, het geanimeerde dashboard in een
 * laptop met een telefoon ernaast, acht functiekaarten, een vertrouwensbalk met alleen waarmaakbare claims, de
 * gratis tools in een eigen sectie; in de balk het logoblok, twee uitklapmenu's, het label "Nieuw" en een omlijnde
 * inlogknop met icoon. De rest van de pagina (waarom, functies, prijzen, kennisbank, faq) blijft staan.
 */
class EasyInvoiceHouseStyleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'easyinvoice']);
    }

    public function test_the_home_page_has_the_new_hero_cards_and_trust_bar(): void
    {
        $res = $this->get('/')->assertOk();

        $res->assertSee('class="ei-hero"', false)
            ->assertSee('Factureren, administreren en méér')
            ->assertSee('Alles-in-één platform voor ondernemers')
            ->assertSee('Start 14 dagen gratis')
            ->assertSee('Bekijk de demo')
            ->assertSee('Geen creditcard nodig')
            ->assertSee('id="heroDash"', false)
            ->assertSee('class="ei-screen"', false)
            ->assertSee('class="ei-phone"', false);

        foreach (['Facturatie', 'Offertes', 'Uren &amp; projecten', 'Administraties', 'AI-assistent EASY', 'Betalingen &amp; incasso', 'E-facturatie', 'Boekhouding'] as $card) {
            $res->assertSee('<b>'.$card.'</b>', false);
        }
        $this->assertSame(8, substr_count($res->getContent(), 'class="ei-card"'));

        // Vertrouwensbalk zonder verzonnen cijfers (het ontwerp noemde een Trustpilot-score die er niet is).
        $res->assertSee('class="ei-trustbar"', false)
            ->assertSee('servers in Amsterdam')
            ->assertDontSee('Trustpilot')
            ->assertDontSee('900+');

        // De gratis tools en de oude secties staan er nog.
        $res->assertSee('id="gratis-tools"', false)
            ->assertSee('Gratis factuur maken')
            ->assertSee('Gratis online aanmaning')
            ->assertSee('id="waarom"', false)
            ->assertSee('id="functies"', false)
            ->assertSee('id="prijzen"', false)
            ->assertSee('BOIP-inschrijving');
    }

    public function test_the_bar_shows_the_lockup_the_menus_and_the_login_button(): void
    {
        $res = $this->get('/')->assertOk();

        // Logoblok: tegel, Easy + Invoice in twee kleuren, ®, regel eronder; ook in de voet.
        $res->assertSee('class="lk-mark" src="/images/easyinvoice-favicon-180.png?v=', false)
            ->assertSee('<span class="lk-name">Easy<em>Invoice</em><sup>&reg;</sup></span>', false)
            ->assertSee('Het complete facturatie- en administratieplatform');
        $this->assertSame(2, substr_count($res->getContent(), 'class="lk-mark"'));

        // Uitklapmenu's Functies en Gratis tools, label "Nieuw" bij AI EASY, Support.
        $this->assertSame(2, substr_count($res->getContent(), 'class="nav-has-dd"'));
        $res->assertSee(route('bouw'), false)
            ->assertSee('Overstappen van WeFact')
            ->assertSee(route('btw-calculator'), false)
            ->assertSee('<span class="nav-pill">Nieuw</span>', false)
            ->assertSee('>Support</a>', false)
            ->assertSee('class="btn btn-outline"', false)
            ->assertSee('Inloggen')
            ->assertSee('Start gratis');
    }

    /** 1.106.1: de site vertelt hoe je de webapp (PWA) installeert; er is geen app in de stores. */
    public function test_the_app_page_explains_how_to_install_the_web_app_and_is_linked(): void
    {
        $this->get('/app')->assertOk()
            ->assertSee('als app op je telefoon en computer')
            ->assertSee('Zet op beginscherm')
            ->assertSee('App installeren')
            ->assertSee('installatie-icoon')
            ->assertSee('Waarom staat EasyInvoice niet in de App Store')
            ->assertSee(route('register'), false);

        // Gelinkt vanuit de kop van de startpagina, het menu Functies en de voet; en in de sitemap.
        $this->get('/')->assertOk()
            ->assertSee('class="ei-applink"', false)
            ->assertSee('zo installeer je EasyInvoice')
            ->assertSee('App op telefoon en computer')
            ->assertSee('App installeren');
        $this->get('/sitemap-paginas.xml')->assertOk()->assertSee(url('/app'), false);
    }

    public function test_the_other_brands_keep_their_own_bar(): void
    {
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);

        $this->get('/')->assertOk()
            ->assertDontSee('class="lk-mark"', false)
            ->assertDontSee('class="ei-hero"', false)
            ->assertSee('class="nav-logo"', false);
    }
}
