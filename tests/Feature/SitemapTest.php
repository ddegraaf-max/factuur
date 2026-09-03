<?php

namespace Tests\Feature;

use App\Models\BusinessCard;
use App\Models\CompanySite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Sitemap-index met deelsitemaps: vaste pagina's, tools, overstappagina's,
 * helpcentrum, kennisbank en de gepubliceerde websites en visitekaartjes van
 * administraties. Lege deelsitemaps blijven uit de index.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_index_lists_sections_that_cover_all_content(): void
    {
        $index = (string) $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString('<sitemapindex', $index);
        foreach (['paginas', 'tools', 'overstappen', 'helpcentrum', 'kennisbank'] as $name) {
            $this->assertStringContainsString(url("/sitemap-{$name}.xml"), $index);
        }
        // Zonder gepubliceerde bedrijfspagina's staat die deelsitemap niet in de index.
        $this->assertStringNotContainsString('sitemap-bedrijven', $index);

        $help = (string) $this->get('/sitemap-helpcentrum.xml')->assertOk()->getContent();
        foreach (array_keys(config('help.articles')) as $slug) {
            $this->assertStringContainsString(url('/helpcentrum/' . $slug), $help);
        }
        $kennisbank = (string) $this->get('/sitemap-kennisbank.xml')->assertOk()->getContent();
        foreach (array_keys(config('kennisbank.articles')) as $slug) {
            $this->assertStringContainsString(url('/kennisbank/' . $slug), $kennisbank);
        }

        $paginas = (string) $this->get('/sitemap-paginas.xml')->assertOk()->getContent();
        $this->assertStringContainsString(url('/zocht-u-een-ander-easyinvoice'), $paginas);
        $this->assertStringContainsString(url('/register'), $paginas);

        $this->get('/sitemap-bestaatniet.xml')->assertNotFound();
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /lang/')->assertSee('/sitemap.xml');
    }

    public function test_published_company_pages_are_listed_with_lastmod(): void
    {
        $company = $this->demoUser()->company;
        $slug = $company->ensurePublicSlug();
        CompanySite::updateOrCreate(['company_id' => $company->id], ['published' => true, 'content' => ['title' => 'Test']]);
        BusinessCard::updateOrCreate(['company_id' => $company->id], ['published' => false]);

        $this->assertStringContainsString(url('/sitemap-bedrijven.xml'), (string) $this->get('/sitemap.xml')->getContent());

        $xml = (string) $this->get('/sitemap-bedrijven.xml')->assertOk()->getContent();
        $this->assertStringContainsString(url('/s/' . $slug), $xml);
        $this->assertStringContainsString('<lastmod>' . now()->toDateString() . '</lastmod>', $xml);
        $this->assertStringNotContainsString('/k/' . $slug, $xml, 'Een niet-gepubliceerd visitekaartje hoort er niet in');

        // Publiceer het kaartje: dan wel.
        BusinessCard::where('company_id', $company->id)->update(['published' => true]);
        $this->assertStringContainsString(url('/k/' . $slug), (string) $this->get('/sitemap-bedrijven.xml')->getContent());

        // Demo-administraties blijven buiten beeld, net als op de pagina's zelf.
        $company->forceFill(['is_demo' => true])->save();
        $this->assertStringNotContainsString('/s/' . $slug, (string) $this->get('/sitemap-bedrijven.xml')->getContent());
    }

    public function test_app_pages_carry_noindex_but_login_and_register_do_not(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('name="robots" content="noindex"', false);
        $this->get('/register')->assertOk()->assertDontSee('name="robots" content="noindex"', false);

        $this->actingAs($this->demoUser());
        $this->get('/dashboard')->assertOk()->assertSee('name="robots" content="noindex"', false);
    }

    public function test_articles_carry_their_updated_date_as_lastmod(): void
    {
        $xml = (string) $this->get('/sitemap-kennisbank.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>' . url('/kennisbank/eindfactuur') . '</loc><lastmod>2026-09-03</lastmod>', $xml);
        $this->assertStringContainsString('<loc>' . url('/kennisbank/factuureisen') . '</loc><lastmod>2026-08-22</lastmod>', $xml);
    }
}
