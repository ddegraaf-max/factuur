<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Inhoud uit configuratie met een eigen deelsitemap (1.80.0): begrippenlijst,
 * pagina's per beroep, voorbeeldfacturen met echte pdf en het overzicht van
 * alle pagina's. Elke pagina rendert, verwijst alleen naar bestaande
 * artikelen en begrippen, en draagt de structured data die erbij hoort.
 */
class MarketingContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_term_renders_with_defined_term_and_breadcrumbs(): void
    {
        $terms = config('begrippen.terms');
        $this->assertGreaterThanOrEqual(30, count($terms));

        $this->get('/begrippen')->assertOk()->assertSee('DefinedTermSet')->assertSee('Btw verlegd');

        foreach ($terms as $slug => $term) {
            $html = (string) $this->get('/begrippen/' . $slug)->assertOk()->getContent();
            $this->assertStringContainsString('"@type":"DefinedTerm"', $html, $slug);
            $this->assertStringContainsString('"@type":"BreadcrumbList"', $html, $slug);
            $this->assertStringContainsString(e($term['term']) . ': betekenis en uitleg', $html, $slug);

            foreach ($term['related'] ?? [] as $related) {
                $this->assertArrayHasKey($related, config('kennisbank.articles'), "{$slug} verwijst naar onbekend kennisbankartikel {$related}");
            }
            foreach ($term['see'] ?? [] as $see) {
                $this->assertArrayHasKey($see, $terms, "{$slug} verwijst naar onbekend begrip {$see}");
            }
        }

        $this->get('/begrippen/bestaat-niet')->assertNotFound();
    }

    public function test_every_profession_renders_with_its_own_faq(): void
    {
        $professions = config('beroepen.professions');
        $this->assertGreaterThanOrEqual(10, count($professions));

        $this->get('/factuurprogramma-voor')->assertOk()->assertSee('Factuurprogramma voor schilders');

        $seenBlocks = [];
        foreach ($professions as $slug => $profession) {
            $html = (string) $this->get('/factuurprogramma-voor/' . $slug)->assertOk()->getContent();
            $this->assertStringContainsString('"@type":"FAQPage"', $html, $slug);
            $this->assertStringContainsString('"@type":"BreadcrumbList"', $html, $slug);
            $this->assertCount(3, $profession['faq'], "{$slug} heeft geen drie vragen");
            $this->assertGreaterThanOrEqual(4, count($profession['blocks']), "{$slug} heeft te weinig blokken");

            // Geen sjabloontekst: elk blok komt maar op één pagina voor.
            foreach ($profession['blocks'] as [$heading, $text]) {
                $this->assertArrayNotHasKey($text, $seenBlocks, "{$slug} herhaalt een blok van " . ($seenBlocks[$text] ?? '?'));
                $seenBlocks[$text] = $slug;
            }
            foreach ($profession['related'] ?? [] as $related) {
                $this->assertArrayHasKey($related, config('kennisbank.articles'), "{$slug} verwijst naar onbekend artikel {$related}");
            }
            foreach ($profession['begrippen'] ?? [] as $term) {
                $this->assertArrayHasKey($term, config('begrippen.terms'), "{$slug} verwijst naar onbekend begrip {$term}");
            }
        }

        $this->get('/factuurprogramma-voor/bestaat-niet')->assertNotFound();
    }

    public function test_examples_render_and_serve_a_real_pdf(): void
    {
        $examples = config('voorbeelden.examples');
        $this->assertGreaterThanOrEqual(4, count($examples));

        $this->get('/voorbeelden')->assertOk()->assertSee('Voorbeeld factuur met btw verlegd');

        foreach ($examples as $slug => $example) {
            $this->get('/voorbeelden/' . $slug)->assertOk()
                ->assertSee('"@type":"BreadcrumbList"', false)
                ->assertSee(url('/voorbeelden/' . $slug . '.pdf'), false);

            $pdf = $this->get('/voorbeelden/' . $slug . '.pdf')->assertOk()
                ->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', (string) $pdf->getContent(), $slug);
            $this->assertStringContainsString('inline', (string) $pdf->headers->get('Content-Disposition'));

            // De html-weergave voor het iframe: zelfde factuur, niet apart te indexeren.
            $this->get('/voorbeelden/' . $slug . '/weergave')->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex')
                ->assertSee($example['data']['van_bedrijf'])
                ->assertSee($example['data']['factuurnummer']);
        }

        $download = $this->get('/voorbeelden/factuur.pdf?download=1')->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('Content-Disposition'));

        $this->get('/voorbeelden/bestaat-niet')->assertNotFound();
        $this->get('/voorbeelden/bestaat-niet.pdf')->assertNotFound();
    }

    public function test_the_overview_lists_every_public_page_from_the_sitemaps(): void
    {
        $html = (string) $this->get('/overzicht')->assertOk()->getContent();

        foreach (['/begrippen/btw-verlegd', '/factuurprogramma-voor/schilders', '/voorbeelden/factuur', '/kennisbank/factuureisen', '/helpcentrum', '/gratis-factuur-maken'] as $path) {
            $this->assertStringContainsString('href="' . url($path) . '"', $html, $path);
        }
        $this->assertStringContainsString('Factuurprogramma voor schilders', $html);
        $this->assertStringContainsString('Btw verlegd', $html);
        $this->assertStringNotContainsString('/s/', $html, 'Klantpagina\'s horen niet in het overzicht');
    }

    public function test_footer_links_to_the_new_sections(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('begrippen'), false)
            ->assertSee(route('beroepen'), false)
            ->assertSee(route('voorbeelden'), false)
            ->assertSee(route('overzicht'), false);
    }
}
