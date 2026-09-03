<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;

/**
 * Sitemaps voor zoekmachines: /sitemap.xml is een index met per onderwerp
 * een deelsitemap (/sitemap-{naam}.xml). Zo laat Search Console per onderdeel
 * zien hoeveel pagina's gevonden en geïndexeerd zijn. Welke pagina's erin
 * horen bepaalt App\Services\SitemapService; lege deelsitemaps blijven uit
 * de index.
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

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($sections[$name]() as [$path, $lastmod]) {
            $xml .= '  <url><loc>' . e(url($path)) . '</loc>'
                . ($lastmod ? '<lastmod>' . $lastmod->toDateString() . '</lastmod>' : '')
                . '</url>' . "\n";
        }
        $xml .= '</urlset>';

        return $this->xml($xml);
    }

    private function xml(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
