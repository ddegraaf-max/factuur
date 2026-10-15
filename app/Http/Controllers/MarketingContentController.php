<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use App\Support\FreeInvoicePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Publieke inhoudspagina's uit configuratie, elk met een eigen deelsitemap:
 * de begrippenlijst (config/begrippen.php), de pagina's per beroep
 * (config/beroepen.php), de voorbeeldfacturen met echte pdf
 * (config/voorbeelden.php) en het overzicht van alle pagina's (/overzicht).
 */
class MarketingContentController extends Controller
{
    public function begrippen(): View
    {
        return view('marketing.begrippen', ['terms' => config('begrippen.terms', [])]);
    }

    public function begrip(string $slug): View
    {
        $terms = config('begrippen.terms', []);
        abort_unless(isset($terms[$slug]), 404);

        return view('marketing.begrip', [
            'slug' => $slug,
            'term' => $terms[$slug],
            'terms' => $terms,
            'articles' => config('kennisbank.articles', []),
        ]);
    }

    public function beroepen(): View
    {
        return view('marketing.beroepen', ['professions' => config('beroepen.professions', [])]);
    }

    public function beroep(string $slug): View
    {
        $professions = config('beroepen.professions', []);
        abort_unless(isset($professions[$slug]), 404);

        return view('marketing.beroep', [
            'slug' => $slug,
            'profession' => $professions[$slug],
            'professions' => $professions,
            'articles' => config('kennisbank.articles', []),
            'terms' => config('begrippen.terms', []),
        ]);
    }

    public function voorbeelden(): View
    {
        return view('marketing.voorbeelden', ['examples' => config('voorbeelden.examples', [])]);
    }

    public function voorbeeld(string $slug): View
    {
        $examples = config('voorbeelden.examples', []);
        abort_unless(isset($examples[$slug]), 404);

        return view('marketing.voorbeeld', [
            'slug' => $slug,
            'example' => $examples[$slug],
            'examples' => $examples,
            'articles' => config('kennisbank.articles', []),
            'terms' => config('begrippen.terms', []),
        ]);
    }

    /** De pdf van een voorbeeld: in de browser (iframe) of als download met ?download=1. */
    public function voorbeeldPdf(Request $request, string $slug)
    {
        $examples = config('voorbeelden.examples', []);
        abort_unless(isset($examples[$slug]), 404);

        // De inhoud is vast; een dag cachen scheelt per bezoek het renderen.
        $binary = Cache::remember('voorbeeld-pdf:' . $slug . ':' . ($examples[$slug]['updated'] ?? ''), now()->addDay(), function () use ($examples, $slug) {
            return FreeInvoicePdf::make($examples[$slug]['data'])->setPaper('a4')->output();
        });

        $filename = 'voorbeeld-' . $slug . '.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline') . '; filename="' . $filename . '"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Dezelfde factuur als html, voor het iframe op de voorbeeldpagina: een pdf
     * in een iframe blijft op telefoons (en in headless browsers) leeg. Geen
     * eigen pagina voor zoekmachines; de pdf blijft de download.
     */
    public function voorbeeldHtml(string $slug)
    {
        $examples = config('voorbeelden.examples', []);
        abort_unless(isset($examples[$slug]), 404);

        $html = view('pdf.gratis-factuur', FreeInvoicePdf::viewData($examples[$slug]['data']))->render();
        // Het sjabloon rekent op @page-marges en een vaste voet; in de browser krijgt het een eigen kader.
        $html = str_replace('</head>', '<style>body{margin:0;padding:32px 36px 40px;background:#fff;max-width:720px}.credit{position:static;margin-top:36px}</style></head>', $html);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Overzicht van alle openbare pagina's voor bezoekers én zoekmachines:
     * dezelfde bron als de sitemaps, met een leesbare naam per pagina.
     */
    public function overzicht(SitemapService $sitemap): View
    {
        $names = [
            'paginas' => 'Pagina\'s', 'tools' => 'Gratis tools', 'overstappen' => 'Overstappen van een ander pakket',
            'helpcentrum' => 'Helpcentrum', 'kennisbank' => 'Kennisbank', 'begrippen' => 'Begrippenlijst',
            'beroepen' => 'Factuurprogramma per beroep', 'voorbeelden' => 'Voorbeeldfacturen',
        ];

        $groups = [];
        foreach ($sitemap->sections() as $key => $pages) {
            if (! isset($names[$key])) {
                continue; // gepubliceerde klantpagina's horen niet in dit overzicht
            }
            $links = [];
            foreach ($pages() as [$path]) {
                $links[] = ['url' => url($path), 'label' => $this->label($path)];
            }
            if ($links !== []) {
                $groups[] = ['title' => $names[$key], 'links' => $links];
            }
        }

        return view('marketing.overzicht', ['groups' => $groups]);
    }

    /** Leesbare naam van een pad: uit de artikelconfiguraties, anders uit een vaste lijst. */
    private function label(string $path): string
    {
        $prefixes = [
            '/helpcentrum/' => ['help.articles', 'title'],
            '/kennisbank/' => ['kennisbank.articles', 'title'],
            '/begrippen/' => ['begrippen.terms', 'term'],
            '/factuurprogramma-voor/' => ['beroepen.professions', 'title'],
            '/voorbeelden/' => ['voorbeelden.examples', 'title'],
        ];
        foreach ($prefixes as $prefix => [$config, $field]) {
            if (str_starts_with($path, $prefix)) {
                $slug = substr($path, strlen($prefix));
                $value = config($config . '.' . $slug . '.' . $field);
                if (is_string($value) && $value !== '') {
                    return str_replace('{brand}', brand('name'), $value);
                }
            }
        }

        $fixed = [
            '/' => 'Home', '/over-ons' => 'Over ons', '/contact' => 'Contact', '/demo' => 'Demo',
            '/veelgestelde-vragen' => 'Veelgestelde vragen', '/helpcentrum' => 'Helpcentrum', '/kennisbank' => 'Kennisbank',
            '/facturatie-met-ai' => 'Factureren met AI', '/boekhouders' => 'Voor boekhouders',
            '/factuurprogramma-bouw' => 'Factuurprogramma voor de bouw', '/roadmap' => 'Roadmap', '/wat-is-nieuw' => 'Wat is nieuw',
            '/status' => 'Status', '/privacy' => 'Privacybeleid', '/voorwaarden' => 'Algemene voorwaarden', '/cookies' => 'Cookies',
            '/verwerkersovereenkomst' => 'Verwerkersovereenkomst', '/login' => 'Inloggen', '/register' => 'Account aanmaken',
            '/zocht-u-een-ander-easyinvoice' => 'Zocht u een ander ' . brand('name') . '?',
            '/gratis-factuur-maken' => 'Gratis factuur maken', '/btw-calculator' => 'Btw-calculator',
            '/uurtarief-calculator' => 'Uurtarief-calculator', '/incassokosten-berekenen' => 'Incassokosten berekenen',
            '/online-aanmaning' => 'Gratis online aanmaning',
            '/overstappen-van/wefact' => 'Overstappen van WeFact', '/overstappen-van/moneybird' => 'Overstappen van Moneybird',
            '/overstappen-van/e-boekhouden' => 'Overstappen van e-Boekhouden.nl',
            '/begrippen' => 'Begrippenlijst', '/factuurprogramma-voor' => 'Factuurprogramma per beroep',
            '/voorbeelden' => 'Voorbeeldfacturen', '/overzicht' => 'Overzicht van alle pagina\'s',
        ];

        return $fixed[$path] ?? ltrim($path, '/');
    }
}
