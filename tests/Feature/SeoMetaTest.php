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
        '/privacy', '/voorwaarden', '/demo', '/login', '/register',
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
