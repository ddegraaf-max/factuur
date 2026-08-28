<?php

namespace App\Services;

use App\Models\Company;
use App\Services\Ai\StructuredClaude;

/**
 * Website maken met AI: uit een paar antwoorden (wat doe je, voor wie, waarom
 * jij, toon) komt de complete inhoud van een one-pager — hero, diensten,
 * over, USP's, contact en SEO-teksten. De gebruiker bewerkt en publiceert
 * zelf; de pagina wordt in de huisstijl getoond onder /s/{slug}.
 */
class SiteGeneratorService
{
    public function __construct(private StructuredClaude $claude)
    {
    }

    public function enabled(): bool
    {
        return $this->claude->enabled();
    }

    /** @return array<string, mixed> */
    public function generate(Company $company, array $answers): array
    {
        return self::sanitize($this->claude->json($this->prompt($company, $answers), $this->schema(), 8000, 'medium', [], 'Website maken'));
    }

    /** Lege structuur voor de editor. */
    public static function blank(): array
    {
        return [
            'hero' => ['title' => '', 'subtitle' => '', 'cta' => 'Vraag een offerte aan'],
            'services' => [], 'about' => ['title' => 'Over ons', 'text' => ''], 'usps' => [],
            'contact' => ['title' => 'Neem contact op', 'text' => ''], 'seo' => ['title' => '', 'description' => ''],
        ];
    }

    /** Inhoud (van AI of uit de editor) opschonen en begrenzen. */
    public static function sanitize(array $c): array
    {
        $t = fn ($v, int $max) => mb_substr(trim((string) (is_scalar($v) ? $v : '')), 0, $max);
        $list = function ($items, int $max, string $textKey, int $titleMax, int $textMax) use ($t) {
            $out = [];
            foreach (array_slice(is_array($items) ? $items : [], 0, $max) as $item) {
                $title = $t($item['title'] ?? '', $titleMax);
                $text = $t($item[$textKey] ?? '', $textMax);
                if ($title !== '' || $text !== '') {
                    $out[] = ['title' => $title, $textKey => $text];
                }
            }

            return $out;
        };

        return [
            'hero' => ['title' => $t($c['hero']['title'] ?? '', 90), 'subtitle' => $t($c['hero']['subtitle'] ?? '', 240), 'cta' => $t($c['hero']['cta'] ?? '', 40) ?: 'Vraag een offerte aan'],
            'services' => $list($c['services'] ?? [], 6, 'description', 60, 320),
            'about' => ['title' => $t($c['about']['title'] ?? '', 80) ?: 'Over ons', 'text' => $t($c['about']['text'] ?? '', 1200)],
            'usps' => $list($c['usps'] ?? [], 4, 'text', 60, 220),
            'contact' => ['title' => $t($c['contact']['title'] ?? '', 80) ?: 'Neem contact op', 'text' => $t($c['contact']['text'] ?? '', 320)],
            'seo' => ['title' => $t($c['seo']['title'] ?? '', 70), 'description' => $t($c['seo']['description'] ?? '', 160)],
        ];
    }

    private function schema(): array
    {
        $s = fn (string $d) => ['type' => 'string', 'description' => $d];
        $titled = fn (string $key, string $d) => ['type' => 'object', 'properties' => ['title' => $s('Korte titel'), $key => $s($d)], 'required' => ['title', $key], 'additionalProperties' => false];

        return [
            'type' => 'object',
            'properties' => [
                'hero' => ['type' => 'object', 'properties' => ['title' => $s('Krachtige kop van maximaal 10 woorden'), 'subtitle' => $s('Twee zinnen: wat je doet en voor wie'), 'cta' => $s('Knoptekst, bijv. Vraag een offerte aan')], 'required' => ['title', 'subtitle', 'cta'], 'additionalProperties' => false],
                'services' => ['type' => 'array', 'items' => $titled('description', 'Twee zinnen over deze dienst'), 'description' => 'Vier tot zes diensten'],
                'about' => $titled('text', 'Drie tot vijf zinnen over het bedrijf, in de wij-vorm'),
                'usps' => ['type' => 'array', 'items' => $titled('text', 'Eén zin'), 'description' => 'Drie tot vier redenen om voor dit bedrijf te kiezen'],
                'contact' => $titled('text', 'Eén uitnodigende zin boven het contactformulier'),
                'seo' => ['type' => 'object', 'properties' => ['title' => $s('Paginatitel voor Google, max 60 tekens, met plaatsnaam als bekend'), 'description' => $s('Meta-omschrijving, max 155 tekens')], 'required' => ['title', 'description'], 'additionalProperties' => false],
            ],
            'required' => ['hero', 'services', 'about', 'usps', 'contact', 'seo'],
            'additionalProperties' => false,
        ];
    }

    private function prompt(Company $company, array $answers): string
    {
        $clean = fn ($key, $fallback) => mb_substr(trim((string) ($answers[$key] ?? '')), 0, 300) ?: $fallback;

        return implode("\n", [
            'Je bent tekstschrijver voor kleine Nederlandse ondernemers. Schrijf de complete inhoud van een one-page website voor dit bedrijf.',
            'Bedrijfsnaam: ' . $company->publicName(),
            'Plaats: ' . ($company->city ?: 'onbekend'),
            'Wat het bedrijf doet: ' . $clean('what', 'onbekend'),
            'Doelgroep: ' . $clean('audience', 'onbekend'),
            'Waarom klanten voor dit bedrijf kiezen: ' . $clean('why', 'onbekend'),
            'Toon: ' . $clean('tone', 'vriendelijk en professioneel'),
            '',
            'Regels: schrijf in het Nederlands in de je-vorm naar de klant en de wij-vorm over het bedrijf; concreet en zonder holle marketingtaal; verzin geen certificeringen, prijzen, jaartallen, reviews of cijfers die niet zijn gegeven; vier tot zes diensten; drie tot vier USP\'s; de kop van de hero is maximaal tien woorden.',
        ]);
    }
}
