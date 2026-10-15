<?php

namespace App\Services;

use App\Models\Company;
use App\Support\Brand;
use App\Support\Market;
use Illuminate\Support\Carbon;

/**
 * Welke pagina's in de sitemaps horen, per markt en per onderwerp.
 *
 * Eén bron voor de sitemap-index (/sitemap.xml), de deelsitemaps en de
 * IndexNow-aanmelding (seo:indexnow). Lastmod alleen waar we hem echt
 * weten: de 'updated'-datum van een artikel en de wijzigingsdatum van een
 * gepubliceerde website of visitekaartje. Een gegokte datum negeert Google.
 */
class SitemapService
{
    /**
     * Deelsitemaps: naam => functie die [pad, lastmod|null]-paren geeft.
     *
     * @return array<string, \Closure(): list<array{0: string, 1: Carbon|null}>>
     */
    public function sections(): array
    {
        if (Brand::is('fachora')) {
            $categories = array_keys((array) config('fachora.categories', []));

            return [
                'strony' => fn () => $this->plain(['/', '/zlecenie/nowe', '/jak-to-dziala', '/fachora-protect', '/standard', '/dla-firm', '/dla-firm/zglos',
                    '/przejmij', '/faq', '/kontakt', '/o-nas', '/regulamin', '/polityka-prywatnosci', '/status', '/login']),
                'uslugi' => fn () => $this->plain(array_map(fn ($c) => '/uslugi/' . $c, $categories)),
                'miasta' => fn () => $this->categoryCityPages(),
                'firmy' => fn () => $this->providerPages(),
            ];
        }

        if (Market::isPl()) {
            return [
                'strony' => fn () => $this->plain(['/', '/demo', '/faq', '/kontakt', '/o-nas', '/skup-wyrokow',
                    '/regulamin', '/polityka-prywatnosci', '/status', '/login', '/register']),
                'narzedzia' => fn () => $this->plain(['/kalkulator-odsetek']),
                'przenies' => fn () => $this->plain(array_map(fn ($p) => '/przenies-sie-z/' . $p, array_keys(config('przenies.packages', [])))),
                'firmy' => fn () => $this->companyPages(),
            ];
        }

        return [
            'paginas' => fn () => $this->plain(array_merge([
                '/', '/over-ons', '/contact', '/demo', '/veelgestelde-vragen', '/helpcentrum', '/kennisbank',
                '/facturatie-met-ai', '/boekhouders', '/factuurprogramma-bouw', '/roadmap', '/wat-is-nieuw', '/status',
                '/privacy', '/voorwaarden', '/cookies', '/verwerkersovereenkomst', '/login', '/register',
                '/begrippen', '/factuurprogramma-voor', '/voorbeelden', '/overzicht',
            ], Brand::watchesTrademark() ? ['/zocht-u-een-ander-easyinvoice'] : [])),
            'tools' => fn () => $this->plain(['/gratis-factuur-maken', '/btw-calculator', '/uurtarief-calculator', '/incassokosten-berekenen', '/online-aanmaning']),
            'overstappen' => fn () => $this->plain(['/overstappen-van/wefact', '/overstappen-van/moneybird', '/overstappen-van/e-boekhouden']),
            'helpcentrum' => fn () => $this->articles('/helpcentrum/', config('help.articles', [])),
            'kennisbank' => fn () => $this->articles('/kennisbank/', config('kennisbank.articles', [])),
            // Inhoud uit configuratie, elk met een eigen deelsitemap (1.80.0).
            'begrippen' => fn () => $this->articles('/begrippen/', config('begrippen.terms', [])),
            'beroepen' => fn () => $this->articles('/factuurprogramma-voor/', config('beroepen.professions', [])),
            'voorbeelden' => fn () => $this->articles('/voorbeelden/', config('voorbeelden.examples', [])),
            'bedrijven' => fn () => $this->companyPages(),
        ];
    }

    /**
     * Alle absolute URL's uit alle deelsitemaps (voor IndexNow).
     *
     * @return list<string>
     */
    public function allUrls(): array
    {
        $urls = [];
        foreach ($this->sections() as $pages) {
            foreach ($pages() as [$path]) {
                $urls[] = url($path);
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param  list<string>  $paths
     * @return list<array{0: string, 1: null}>
     */
    private function plain(array $paths): array
    {
        return array_map(fn ($p) => [$p, null], $paths);
    }

    /**
     * Artikelen uit config/help.php en config/kennisbank.php; 'updated' (Y-m-d) wordt lastmod.
     *
     * @param  array<string, array<string, mixed>>  $articles
     * @return list<array{0: string, 1: Carbon|null}>
     */
    private function articles(string $prefix, array $articles): array
    {
        $pages = [];
        foreach ($articles as $slug => $article) {
            $updated = $article['updated'] ?? null;
            $pages[] = [$prefix . $slug, is_string($updated) && $updated !== '' ? Carbon::parse($updated) : null];
        }

        return $pages;
    }

    /**
     * Fachora: categoriepagina's per stad, alleen waar Verified bedrijven zijn
     * (anders zijn het lege doorway-pagina's).
     *
     * @return list<array{0: string, 1: Carbon|null}>
     */
    private function categoryCityPages(): array
    {
        $rows = \Illuminate\Support\Facades\DB::table('providers')
            ->join('provider_category', 'provider_category.provider_id', '=', 'providers.id')
            ->where('providers.status', 'verified')->whereNotNull('providers.city')
            ->selectRaw('provider_category.category as category, providers.city as city, MAX(providers.updated_at) as updated')
            ->groupBy('provider_category.category', 'providers.city')
            ->get();

        $pages = [];
        foreach ($rows as $row) {
            $pages[] = ['/uslugi/' . $row->category . '/' . \Illuminate\Support\Str::slug(\Illuminate\Support\Str::ascii((string) $row->city)), $row->updated ? Carbon::parse($row->updated) : null];
        }

        return $pages;
    }

    /**
     * Fachora: de profielen van Verified bedrijven.
     *
     * @return list<array{0: string, 1: Carbon|null}>
     */
    private function providerPages(): array
    {
        return \App\Models\Provider::query()->verified()->orderBy('id')->get(['slug', 'updated_at'])
            ->map(fn ($p) => ['/firma/' . $p->slug, $p->updated_at])->all();
    }

    /**
     * Gepubliceerde websites (/s/{slug}) en visitekaartjes (/k/{slug}) van
     * administraties, onder dezelfde voorwaarden als de pagina's zelf:
     * gepubliceerd, geen demo, en een administratie met toegang.
     *
     * @return list<array{0: string, 1: Carbon|null}>
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
}
