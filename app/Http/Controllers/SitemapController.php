<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\Brand;
use App\Support\Market;
use Illuminate\Http\Response;

/**
 * Sitemaps voor zoekmachines.
 *
 * /sitemap.xml is een index met per onderwerp een eigen deelsitemap
 * (/sitemap-{naam}.xml). Zo laat Search Console per onderdeel zien hoeveel
 * pagina's gevonden en geïndexeerd zijn, en lopen nieuwe help- en
 * kennisbankartikelen en gepubliceerde websites en visitekaartjes van
 * administraties automatisch mee. Deelsitemaps zonder pagina's staan niet
 * in de index.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($this->sections() as $name => $pages) {
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
        $sections = $this->sections();
        abort_unless(isset($sections[$name]), 404);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($sections[$name]() as $entry) {
            [$path, $lastmod] = is_array($entry) ? $entry : [$entry, null];
            $xml .= '  <url><loc>' . e(url($path)) . '</loc>'
                . ($lastmod ? '<lastmod>' . $lastmod->toDateString() . '</lastmod>' : '')
                . '</url>' . "\n";
        }
        $xml .= '</urlset>';

        return $this->xml($xml);
    }

    /**
     * Deelsitemaps per markt: naam => functie die paden geeft (of [pad, lastmod]).
     * Lastmod alleen waar we hem echt weten; een gegokte datum negeert Google.
     *
     * @return array<string, \Closure(): array<int, string|array{0: string, 1: \Illuminate\Support\Carbon|null}>>
     */
    private function sections(): array
    {
        if (Market::isPl()) {
            return [
                'strony' => fn () => ['/', '/demo', '/faq', '/kontakt', '/o-nas', '/skup-wyrokow',
                    '/regulamin', '/polityka-prywatnosci', '/status', '/login', '/register'],
                'narzedzia' => fn () => ['/kalkulator-odsetek'],
                'przenies' => fn () => array_map(fn ($p) => '/przenies-sie-z/' . $p, array_keys(config('przenies.packages', []))),
                'firmy' => fn () => $this->companyPages(),
            ];
        }

        return [
            'paginas' => fn () => array_merge([
                '/', '/over-ons', '/contact', '/demo', '/veelgestelde-vragen', '/helpcentrum', '/kennisbank',
                '/facturatie-met-ai', '/boekhouders', '/roadmap', '/wat-is-nieuw', '/status',
                '/privacy', '/voorwaarden', '/cookies', '/verwerkersovereenkomst', '/login', '/register',
            ], Brand::watchesTrademark() ? ['/zocht-u-een-ander-easyinvoice'] : []),
            'tools' => fn () => ['/gratis-factuur-maken', '/btw-calculator', '/uurtarief-calculator'],
            'overstappen' => fn () => ['/overstappen-van/wefact', '/overstappen-van/moneybird', '/overstappen-van/e-boekhouden'],
            'helpcentrum' => fn () => array_map(fn ($s) => '/helpcentrum/' . $s, array_keys(config('help.articles', []))),
            'kennisbank' => fn () => array_map(fn ($s) => '/kennisbank/' . $s, array_keys(config('kennisbank.articles', []))),
            'bedrijven' => fn () => $this->companyPages(),
        ];
    }

    /**
     * Gepubliceerde websites (/s/{slug}) en visitekaartjes (/k/{slug}) van
     * administraties — dezelfde voorwaarden als de pagina's zelf: gepubliceerd,
     * geen demo, en een administratie met toegang.
     *
     * @return array<int, array{0: string, 1: \Illuminate\Support\Carbon|null}>
     */
    private function companyPages(): array
    {
        $companies = Company::query()
            ->whereNotNull('public_slug')
            ->where(fn ($q) => $q
                ->whereHas('site', fn ($s) => $s->where('published', true))
                ->orWhereHas('businessCard', fn ($c) => $c->where('published', true)))
            ->with(['site', 'businessCard'])
            ->orderBy('id')
            ->get();

        $pages = [];
        foreach ($companies as $company) {
            if (! $company->publicPagesAllowed()) {
                continue;
            }
            if ($company->site?->published) {
                $pages[] = ['/s/' . $company->public_slug, $company->site->updated_at];
            }
            if ($company->businessCard?->published) {
                $pages[] = ['/k/' . $company->public_slug, $company->businessCard->updated_at];
            }
        }

        return $pages;
    }

    private function xml(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
