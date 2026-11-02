<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use App\Support\Market;
use Illuminate\Http\Response;

/**
 * Sitemaps voor zoekmachines: /sitemap.xml is een index met per onderwerp
 * een deelsitemap (/sitemap-{naam}.xml). Zo laat Search Console per onderdeel
 * zien hoeveel pagina's gevonden en geïndexeerd zijn. Welke pagina's erin
 * horen bepaalt App\Services\SitemapService; lege deelsitemaps blijven uit
 * de index.
 *
 * Markten met twee UI-talen (Polen: pl + en) krijgen per pagina beide
 * taalversies als eigen <url> met xhtml:link-alternates (1.92); de Engelse
 * versie leeft op dezelfde URL met ?lang=en, net als de canonical in de layout.
 */
class SitemapController extends Controller
{
    public function __construct(private SitemapService $sitemap)
    {
    }

    public function index(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($this->sitemap->sections() as $name => $pages) {
            if ($pages() === []) {
                continue;
            }
            $xml .= '  <sitemap><loc>' . e(url("/sitemap-{$name}.xml")) . '</loc></sitemap>' . "\n";
        }
        $xml .= '</sitemapindex>';

        return $this->xml($xml);
    }

    public function show(string $name): Response
    {
        $sections = $this->sitemap->sections();
        abort_unless(isset($sections[$name]), 404);

        $locales = Market::uiLocales();
        $default = Market::locale();
        $multi = count($locales) > 1;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . ($multi ? ' xmlns:xhtml="http://www.w3.org/1999/xhtml"' : '') . '>' . "\n";
        foreach ($sections[$name]() as [$path, $lastmod]) {
            $base = $path === '/' ? url('/') . '/' : url($path); // de startpagina met slash, zoals de canonical
            $variants = $multi
                ? array_map(fn (string $l) => [$l, $l === $default ? $base : $base . '?lang=' . $l], $locales)
                : [[$default, $base]];
            $alternates = '';
            if ($multi) {
                foreach ($variants as [$l, $href]) {
                    $alternates .= '<xhtml:link rel="alternate" hreflang="' . $l . '" href="' . e($href) . '"/>';
                }
                $alternates .= '<xhtml:link rel="alternate" hreflang="x-default" href="' . e($base) . '"/>';
            }
            foreach ($variants as [, $href]) {
                $xml .= '  <url><loc>' . e($href) . '</loc>'
                    . ($lastmod ? '<lastmod>' . $lastmod->toDateString() . '</lastmod>' : '')
                    . $alternates
                    . '</url>' . "\n";
            }
        }
        $xml .= '</urlset>';

        return $this->xml($xml);
    }

    private function xml(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
