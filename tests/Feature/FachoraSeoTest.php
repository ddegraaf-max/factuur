<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO van fachora.pl (1.92): twee taalversies per pagina (pl op het pad, en op ?lang=en) met eigen
 * canonical en hreflang-alternates, sitemaps met beide versies, stadspagina's voor de SEO-steden
 * (en 404 voor onbekende steden), een indexeerbare aanvraagpagina en structured data op de
 * categoriepagina's. EasyInvoice (één taal) blijft zonder hreflang.
 */
class FachoraSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
    }

    public function test_every_language_version_has_its_own_canonical_and_hreflang(): void
    {
        $pl = (string) $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/">', $pl);
        $this->assertStringContainsString('<link rel="alternate" hreflang="pl" href="http://localhost/">', $pl);
        $this->assertStringContainsString('<link rel="alternate" hreflang="en" href="http://localhost/?lang=en">', $pl);
        $this->assertStringContainsString('<link rel="alternate" hreflang="x-default" href="http://localhost/">', $pl);
        $this->assertStringContainsString('<meta property="og:url" content="http://localhost/">', $pl);

        $en = (string) $this->get('/?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('<html lang="en"', $en);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/?lang=en">', $en);
        $this->assertStringContainsString('<link rel="alternate" hreflang="pl" href="http://localhost/">', $en);
        $this->assertStringContainsString('<meta property="og:url" content="http://localhost/?lang=en">', $en);

        $cat = (string) $this->get('/uslugi/hydraulik?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/uslugi/hydraulik?lang=en">', $cat);
        $this->assertStringContainsString('<link rel="alternate" hreflang="pl" href="http://localhost/uslugi/hydraulik">', $cat);
    }

    public function test_the_sitemaps_list_both_languages_with_alternates(): void
    {
        $index = (string) $this->get('/sitemap.xml')->assertOk()->getContent();
        foreach (['strony', 'uslugi', 'miasta'] as $section) {
            $this->assertStringContainsString("http://localhost/sitemap-{$section}.xml", $index, "deelsitemap {$section} ontbreekt in de index");
        }

        $strony = (string) $this->get('/sitemap-strony.xml')->assertOk()->getContent();
        $this->assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $strony);
        $this->assertStringContainsString('<loc>http://localhost/</loc>', $strony, 'de startpagina met slash, zoals de canonical');
        $this->assertStringContainsString('<loc>http://localhost/?lang=en</loc>', $strony);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="en" href="http://localhost/jak-to-dziala?lang=en"/>', $strony);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="x-default" href="http://localhost/jak-to-dziala"/>', $strony);
        $this->assertStringNotContainsString('/login', $strony);
        $this->assertStringNotContainsString('/status', $strony);

        $miasta = (string) $this->get('/sitemap-miasta.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>http://localhost/uslugi/hydraulik/warszawa</loc>', $miasta);
        $this->assertStringContainsString('<loc>http://localhost/uslugi/hydraulik/warszawa?lang=en</loc>', $miasta);
        $this->assertStringContainsString('<loc>http://localhost/uslugi/mechanik/olsztyn</loc>', $miasta);
        $this->assertSame(
            count(config('fachora.categories')) * count(config('fachora.seo_cities')) * 2,
            substr_count($miasta, '<url>'),
            'elke categorie × elke SEO-stad, in twee talen'
        );
    }

    public function test_city_pages_exist_for_the_seo_cities_and_unknown_cities_are_404(): void
    {
        $krakow = (string) $this->get('/uslugi/hydraulik/krakow')->assertOk()->getContent();
        $this->assertStringContainsString('<h1>Hydraulik – Kraków</h1>', $krakow, 'de stadsnaam met diakrieten uit config fachora.seo_cities');
        $this->assertStringContainsString('Hydraulik w mieście Kraków: sam wybierasz firmę', $krakow, 'zonder Verified bedrijf vertelt de pagina hoe de klant zelf kiest');
        $this->assertStringNotContainsString('Wkrótce', $krakow);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $krakow);
        $this->assertStringContainsString('"@type":"City","name":"Kraków"', $krakow);
        $this->assertStringContainsString('/uslugi/hydraulik/warszawa', $krakow, 'stadslinks naar de andere SEO-steden');

        $en = (string) $this->get('/uslugi/hydraulik/krakow?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('Plumber in Kraków: you pick the company', $en);

        $this->get('/uslugi/hydraulik/nieistniejace-miasto')->assertNotFound();
        $this->get('/uslugi/hydraulik/pcim')->assertNotFound();
        $this->get('/uslugi/hydraulik/plock')->assertOk(); // bekend stadsmiddelpunt (config fachora.cities)
    }

    public function test_the_intake_page_is_indexable_with_its_own_title(): void
    {
        $html = (string) $this->get('/zlecenie/nowe')->assertOk()->getContent();
        $this->assertStringNotContainsString('name="robots" content="noindex"', $html);
        $this->assertStringContainsString('Opisz zlecenie i sam wybierz fachowca | Fachora', $html);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/zlecenie/nowe">', $html);
    }

    public function test_the_site_presents_itself_as_a_website_not_as_software(): void
    {
        $html = (string) $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('"@type": "WebSite"', $html);
        $this->assertStringNotContainsString('SoftwareApplication', $html);
        $this->assertStringNotContainsString('"applicationCategory"', $html);
    }

    public function test_easyinvoice_keeps_a_single_language_without_hreflang(): void
    {
        config(['brand.active' => 'easyinvoice']);
        $html = (string) $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('hreflang="x-default"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/">', $html);
        $this->assertStringContainsString('"@type": "SoftwareApplication"', $html);
    }
}
