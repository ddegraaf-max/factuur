<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wat een zoekmachine van de openbare pagina's ziet: een title en description
 * van bruikbare lengte, precies één kop, en lettertypen van het eigen domein
 * (de verbindingen naar Google kostten op mobiel ruim drie seconden).
 */
class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        '/', '/facturatie-met-ai', '/kennisbank', '/boekhouders', '/over-ons', '/contact',
        '/veelgestelde-vragen', '/helpcentrum', '/roadmap', '/wat-is-nieuw', '/status',
        '/privacy', '/voorwaarden', '/demo', '/login', '/register', '/btw-calculator', '/uurtarief-calculator',
        '/gratis-factuur-maken', '/incassokosten-berekenen', '/cookies', '/verwerkersovereenkomst',
        '/overstappen-van/wefact', '/overstappen-van/moneybird', '/overstappen-van/e-boekhouden',
        '/factuurprogramma-bouw', '/online-aanmaning', '/aanmaning-maken',
    ];

    private function meta(string $html, string $pattern): string
    {
        preg_match($pattern, $html, $m);

        return html_entity_decode(trim($m[1] ?? ''), ENT_QUOTES | ENT_HTML5);
    }

    public function test_titles_and_descriptions_have_a_usable_length(): void
    {
        $titles = [];
        foreach (self::PAGES as $path) {
            $html = (string) $this->get($path)->assertOk()->getContent();
            $title = $this->meta($html, '#<title[^>]*>(.*?)</title>#s');
            $description = $this->meta($html, '#<meta name="description" content="([^"]*)"#');

            $this->assertGreaterThanOrEqual(30, mb_strlen($title), "Title van {$path} is te kort: {$title}");
            $this->assertLessThanOrEqual(65, mb_strlen($title), "Title van {$path} is te lang (" . mb_strlen($title) . "): {$title}");
            $this->assertGreaterThanOrEqual(70, mb_strlen($description), "Description van {$path} is te kort");
            $this->assertLessThanOrEqual(165, mb_strlen($description), "Description van {$path} is te lang (" . mb_strlen($description) . ')');
            $titles[$path] = $title;
        }

        $this->assertSame(count($titles), count(array_unique($titles)), 'Elke pagina heeft een eigen title');
    }

    public function test_articles_get_a_title_and_description_that_fit(): void
    {
        $paths = array_merge(
            array_map(fn ($slug) => '/kennisbank/' . $slug, array_keys(config('kennisbank.articles'))),
            array_map(fn ($slug) => '/helpcentrum/' . $slug, array_keys(config('help.articles'))),
        );

        $titles = [];
        foreach ($paths as $path) {
            $html = (string) $this->get($path)->assertOk()->getContent();
            $title = $this->meta($html, '#<title[^>]*>(.*?)</title>#s');
            $description = $this->meta($html, '#<meta name="description" content="([^"]*)"#');

            $this->assertLessThanOrEqual(65, mb_strlen($title), "Title van {$path} is te lang: {$title}");
            $this->assertGreaterThanOrEqual(70, mb_strlen($description), "Description van {$path} is te kort: {$description}");
            $this->assertLessThanOrEqual(165, mb_strlen($description), "Description van {$path} is te lang: {$description}");
            $titles[$path] = $title;
        }

        $this->assertSame(count($titles), count(array_unique($titles)), 'Elk artikel heeft een eigen title');
    }

    public function test_long_texts_are_cut_at_a_sentence_or_a_word(): void
    {
        $this->assertSame('Kort — Merk', \App\Support\Seo::title('Kort — Kennisbank met een heel lange toevoeging erachteraan die niet past — Merk', 'Kort — Merk'));

        $long = 'Betaalt een klant te laat, dan mag je incassokosten en rente rekenen. Hoeveel precies ligt vast in de wet. '
            . 'De staffel, het verschil tussen zakelijke klanten en consumenten, en hoe je het op de aanmaning zet.';
        $this->assertSame(
            'Betaalt een klant te laat, dan mag je incassokosten en rente rekenen. Hoeveel precies ligt vast in de wet.',
            \App\Support\Seo::description($long)
        );

        $oneSentence = str_repeat('woord ', 40);
        $cut = \App\Support\Seo::description($oneSentence);
        $this->assertLessThanOrEqual(165, mb_strlen($cut));
        $this->assertStringEndsWith('woord…', $cut);
    }

    public function test_login_and_register_are_readable_without_javascript(): void
    {
        foreach (['/login', '/register'] as $path) {
            $html = (string) $this->get($path)->assertOk()->getContent();

            $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html), "{$path} heeft precies één kop");
            $this->assertGreaterThanOrEqual(3, preg_match_all('#<a href="' . preg_quote(url('/'), '#') . '#', $html), "{$path} linkt naar andere pagina's");
            $this->assertStringContainsString('id="app" data-page="', $html, 'De app start op dezelfde plek');
            $this->assertStringNotContainsString('name="robots" content="noindex"', $html);
        }

        // De app zelf blijft buiten de zoekmachines.
        $this->get('/forgot-password')->assertOk()->assertSee('name="robots" content="noindex"', false);
    }

    public function test_fonts_come_from_the_own_domain(): void
    {
        $html = (string) $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('fonts.gstatic.com', $html);
        preg_match_all("#url\('(/fonts/[a-z-]+\.woff2)'\)#", $html, $m);
        $this->assertCount(6, $m[1]);
        foreach ($m[1] as $file) {
            $this->assertFileExists(public_path(ltrim($file, '/')));
        }
        $this->assertStringContainsString('rel="preload" as="font" type="font/woff2" href="/fonts/dm-sans-latin.woff2"', $html);
    }
}
