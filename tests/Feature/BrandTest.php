<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Meerdere merken op één codebase (config/brand.php): EasyInvoice is de
 * standaard, Lopra draait op dezelfde code met APP_BRAND=lopra.
 */
class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_easyinvoice_is_the_default_brand(): void
    {
        $this->assertSame('easyinvoice', Brand::key());
        $this->assertSame('EasyInvoice', brand());
        $this->assertSame('EasyInvoice', Brand::name());
        $this->assertSame('hallo@easyinvoice.nl', Brand::email());
        $this->assertSame('hallo@easyinvoice.nl', Brand::contactInbox());
        $this->assertSame('EasyInvoice®', Brand::legalName());
        $this->assertStringStartsWith('Easy ', Brand::version());
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Brand::versionNumber());
        $this->assertTrue(Brand::watchesTrademark());
        $this->assertTrue(Brand::is('easyinvoice'));
        $this->assertFalse(Brand::is('lopra'));
    }

    public function test_unknown_brand_falls_back_to_easyinvoice(): void
    {
        config(['brand.active' => 'bestaat-niet']);

        $this->assertSame('easyinvoice', Brand::key());
        $this->assertSame('EasyInvoice', Brand::name());
    }

    public function test_lopra_values_and_environment_overrides(): void
    {
        config(['brand.active' => 'lopra']);

        $this->assertSame('Lopra', brand('name'));
        $this->assertSame('Je hele administratie op één plek', Brand::tagline());
        $this->assertSame('hallo@lopra.nl', Brand::email());
        $this->assertSame('hallo@lopra.nl', Brand::contactInbox());
        $this->assertSame('Lopra', Brand::legalName());
        $this->assertSame('Lopra ' . Brand::versionNumber(), Brand::version());
        $this->assertFalse(Brand::watchesTrademark());
        $this->assertSame('/brand/lopra/lopra-icon.svg', brand('mark'));
        $this->assertSame('Lopra-team via hallo@lopra.nl (lopra.nl)', Brand::fill('{brand}-team via {brand_email} ({brand_domain})'));

        // Zolang de nieuwe mailbox nog niet bestaat, kan het contactadres per omgeving afwijken.
        config(['brand.overrides.contact_inbox' => 'inbox@example.com']);
        $this->assertSame('hallo@lopra.nl', Brand::email());
        $this->assertSame('inbox@example.com', Brand::contactInbox());

        config(['brand.overrides.email' => 'hoi@example.com']);
        $this->assertSame('hoi@example.com', Brand::email());
    }

    public function test_client_payload_and_manifest_follow_the_brand(): void
    {
        config(['brand.active' => 'lopra', 'app.url' => 'https://lopra.nl']);

        $client = Brand::forClient();
        $this->assertSame('lopra', $client['key']);
        $this->assertSame('Lopra', $client['name']);
        $this->assertSame('https://lopra.nl', $client['url']);
        $this->assertSame('#1C4E7A', $client['color']);
        $this->assertSame('https://lopra.nl/brand/lopra/og-lopra.png', Brand::asset('og_image'));

        $manifest = Brand::manifest();
        $this->assertSame('Lopra', $manifest['name']);
        $this->assertSame('#1C4E7A', $manifest['theme_color']);
        $this->assertSame('/brand/lopra/lopra-icon-512.png', $manifest['icons'][1]['src']);
    }

    public function test_lopra_homepage_manifest_robots_and_login(): void
    {
        config(['brand.active' => 'lopra', 'app.url' => 'https://lopra.nl']);

        $home = $this->get('/')->assertOk();
        $home->assertSee('data-brand="lopra"', false);
        $home->assertSee('Je hele administratie op één plek');
        $home->assertSee('/brand/lopra/theme.css', false);
        $home->assertSee('/brand/lopra/og-lopra.png', false);
        $this->assertStringNotContainsStringIgnoringCase('easyinvoice', $this->visibleHtml($home->getContent()), 'De Lopra-homepage noemt EasyInvoice');

        $this->get('/manifest.webmanifest')->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Lopra')
            ->assertJsonPath('theme_color', '#1C4E7A');

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://lopra.nl/sitemap.xml');

        // Merkbewaking hoort alleen bij het geregistreerde merk.
        $this->get('/zocht-u-een-ander-easyinvoice')->assertNotFound();

        $login = $this->get('/login')->assertOk();
        $login->assertSee('Inloggen bij Lopra');
        $login->assertSee('data-brand-name="Lopra"', false);
        // Inertia zet de props HTML-geëscapet in data-page.
        $login->assertSee('&quot;brand&quot;:{&quot;key&quot;:&quot;lopra&quot;', false);
    }

    public function test_easyinvoice_homepage_keeps_its_own_brand(): void
    {
        $home = $this->get('/')->assertOk();
        $home->assertSee('data-brand="easyinvoice"', false);
        $home->assertSee('EasyInvoice');
        $home->assertSee('BOIP-inschrijving');
        $home->assertDontSee('/brand/lopra/', false);

        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('name', 'EasyInvoice');
        $this->get('/robots.txt')->assertOk()->assertSee('/sitemap.xml');
        $this->get('/zocht-u-een-ander-easyinvoice')->assertOk();
    }

    public function test_lopra_marketing_pages_render_without_easyinvoice_branding(): void
    {
        config(['brand.active' => 'lopra']);

        foreach (['/over-ons', '/contact', '/veelgestelde-vragen', '/voorwaarden', '/privacy', '/cookies',
            '/verwerkersovereenkomst', '/demo', '/roadmap', '/status', '/helpcentrum', '/register', '/boekhouders',
            '/facturatie-met-ai', '/gratis-factuur-maken', '/overstappen-van/wefact', '/kennisbank'] as $path) {
            $response = $this->get($path)->assertOk();
            $this->assertStringNotContainsStringIgnoringCase('easyinvoice', $this->visibleHtml($response->getContent()), "Pagina {$path} bevat nog het EasyInvoice-merk");
        }

        // De sitemap mag geen pagina's noemen die onder Lopra niet bestaan.
        $this->assertStringNotContainsString('zocht-u-een-ander', implode(' ', $this->sitemapUrls()));
    }

    // --------------------------------------------------- de kleuren in de mail

    public function test_de_merktinten_worden_uit_de_merkkleur_berekend(): void
    {
        // EasyInvoice-rood: de lichte tint hoort exact op de oude, met de hand
        // gekozen waarde uit te komen, zodat daar niets verschuift.
        $this->assertSame('#E8231F', Brand::get('color'));
        $this->assertSame('#FEF2F2', Brand::get('color_tint'));

        config(['brand.active' => 'lopra']);
        $this->assertSame('#1C4E7A', Brand::get('color'));

        // Blauw merk, dus een blauwe tint en geen rode.
        foreach (['color_tint', 'color_tint_border', 'color_strong'] as $toon) {
            $hex = Brand::get($toon);
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', (string) $hex, "{$toon} hoort een kleurcode te zijn");

            [$r, $g, $b] = sscanf(substr((string) $hex, 1), '%2x%2x%2x');
            $this->assertGreaterThanOrEqual($r, $b, "{$toon} ({$hex}) is roder dan blauwer; de tint volgt de merkkleur niet");
        }

        // Een merk zonder kleur levert geen onzin op.
        config(['brand.active' => 'bestaat-niet']);
        $this->assertSame('#FEF2F2', Brand::get('color_tint'), 'onbekend merk valt terug op EasyInvoice');
    }

    public function test_de_verificatiemail_draagt_geen_easyinvoice_rood_naar_een_ander_merk(): void
    {
        /*
         * In zeven mailsjablonen stonden de lichte tinten van het
         * EasyInvoice-rood als vaste waarde: #FEF2F2, #FECACA en #7F1310. Die
         * gingen mee naar élk merk. De verificatiecode van Lopra stond daardoor
         * in donkerrode cijfers in een roze vak, met een blauw labeltje erboven.
         *
         * Rood dat een signaal is (te laat tegenover op tijd, in de
         * dagsamenvatting) blijft staan — dat is geen merkkleur.
         */
        config(['brand.active' => 'lopra']);

        // De mail leest alleen de naam; opslaan is niet nodig.
        $gebruiker = new User(['name' => 'Sanne de Boer', 'email' => 'sanne@example.com']);
        $html = (new \App\Mail\VerificationCodeMail($gebruiker, '123456'))->render();

        foreach (['#E8231F', '#B81814', '#7F1310', '#FECACA', '#FEF2F2'] as $easyinvoiceRood) {
            $this->assertStringNotContainsStringIgnoringCase(
                $easyinvoiceRood,
                $html,
                "de Lopra-verificatiemail bevat nog het EasyInvoice-rood {$easyinvoiceRood}"
            );
        }

        // En de merkkleur hoort er juist wél in te staan.
        $this->assertStringContainsStringIgnoringCase((string) Brand::get('color_tint'), $html);
        $this->assertStringContainsStringIgnoringCase((string) Brand::get('color_strong'), $html);
    }

    /**
     * Alleen wat een bezoeker te zien krijgt: scriptblokken eruit. De Ziggy-routelijst
     * bevat bijvoorbeeld de URI van de merkbewakingspagina van EasyInvoice (die onder
     * Lopra 404 geeft) — dat is geen zichtbare merkverwijzing.
     */
    private function visibleHtml(?string $html): string
    {
        return (string) preg_replace('#<script\b[^>]*>.*?</script>#si', '', (string) $html);
    }
}
