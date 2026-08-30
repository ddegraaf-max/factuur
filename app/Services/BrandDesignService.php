<?php

namespace App\Services;

use App\Models\Company;
use App\Services\Ai\StructuredClaude;

/**
 * Huisstijl ontwerpen met AI: uit een paar antwoorden (wat doe je, voor wie,
 * uitstraling, kleurwens) komen drie richtingen — kleuren, lettertype,
 * factuursjabloon, slogan en een logo-voorstel (woordmerk of monogram). De
 * gebruiker kiest er één in het huisstijlscherm; hier wordt niets opgeslagen.
 */
class BrandDesignService
{
    public function __construct(private StructuredClaude $claude)
    {
    }

    public function enabled(): bool
    {
        return $this->claude->enabled();
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<int, array<string, mixed>>
     *
     * @throws \DomainException
     */
    public function propose(Company $company, array $answers): array
    {
        $json = $this->claude->json($this->prompt($company, $answers), $this->schema(), 6000, 'medium', [], 'Huisstijl ontwerpen');

        $directions = [];
        foreach (array_slice((array) ($json['directions'] ?? []), 0, 3) as $d) {
            $brand = self::hex($d['brand_color'] ?? null);
            if ($brand === null) {
                continue;
            }
            $logoText = mb_substr(trim((string) ($d['logo_text'] ?? '')), 0, 30);
            $directions[] = [
                'name' => mb_substr(trim((string) ($d['name'] ?? __('Richting'))), 0, 40),
                'brand_color' => $brand,
                'accent_color' => self::hex($d['accent_color'] ?? null) ?? '#1C1917',
                'font' => in_array($d['font'] ?? null, ['sans', 'serif'], true) ? $d['font'] : 'sans',
                'template' => in_array($d['template'] ?? null, ['modern', 'classic', 'minimal'], true) ? $d['template'] : 'modern',
                'tagline' => mb_substr(trim((string) ($d['tagline'] ?? ''), " \"'"), 0, 120),
                'logo_style' => in_array($d['logo_style'] ?? null, ['wordmark', 'monogram'], true) ? $d['logo_style'] : 'wordmark',
                'logo_text' => $logoText !== '' ? $logoText : $company->publicName(),
                'motivation' => mb_substr(trim((string) ($d['motivation'] ?? '')), 0, 300),
            ];
        }
        if ($directions === []) {
            throw new \DomainException(__('Er kwam geen bruikbaar voorstel terug. Probeer het opnieuw met iets meer toelichting.'));
        }

        return $directions;
    }

    private static function hex(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', trim($value)) ? strtoupper(trim($value)) : null;
    }

    private function schema(): array
    {
        $hex = ['type' => 'string', 'pattern' => '^#[0-9A-Fa-f]{6}$'];
        $direction = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'Korte naam van de richting, bijv. "Warm ambachtelijk".'],
                'brand_color' => $hex + ['description' => 'Hoofdkleur (#RRGGBB), verzadigd genoeg voor knoppen en koppen; geen wit, zwart of lichtgrijs.'],
                'accent_color' => $hex + ['description' => 'Donkere, leesbare tweede kleur voor tekst en accenten.'],
                'font' => ['type' => 'string', 'enum' => ['sans', 'serif']],
                'template' => ['type' => 'string', 'enum' => ['modern', 'classic', 'minimal'], 'description' => 'modern = kleurband en stevig, classic = formeel met lijnen, minimal = veel witruimte.'],
                'tagline' => ['type' => 'string', 'description' => 'Pakkende slogan in het ' . self::language() . ' van maximaal tien woorden, zonder aanhalingstekens.'],
                'logo_style' => ['type' => 'string', 'enum' => ['wordmark', 'monogram']],
                'logo_text' => ['type' => 'string', 'description' => 'Bij wordmark: de bedrijfsnaam of korte variant; bij monogram: 1 tot 3 hoofdletters.'],
                'motivation' => ['type' => 'string', 'description' => 'Eén zin in het ' . self::language() . ': waarom deze richting bij dit bedrijf past.'],
            ],
            'required' => ['name', 'brand_color', 'accent_color', 'font', 'template', 'tagline', 'logo_style', 'logo_text', 'motivation'],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => ['directions' => ['type' => 'array', 'items' => $direction, 'description' => 'Precies drie richtingen.']],
            'required' => ['directions'],
            'additionalProperties' => false,
        ];
    }

    private function prompt(Company $company, array $answers): string
    {
        $clean = fn ($key, $fallback) => mb_substr(trim((string) ($answers[$key] ?? '')), 0, 200) ?: $fallback;

        return implode("\n", [
            'Je bent merkontwerper voor kleine ondernemers in ' . \App\Support\Market::get('country_name', 'Nederland') . '. Ontwerp precies drie duidelijk verschillende huisstijlrichtingen voor dit bedrijf (bijvoorbeeld warm/ambachtelijk, strak/zakelijk, fris/modern).',
            'Bedrijfsnaam: ' . $company->publicName(),
            'Wat het bedrijf doet: ' . $clean('sector', 'onbekend'),
            'Doelgroep: ' . $clean('audience', 'onbekend'),
            'Gewenste uitstraling: ' . $clean('tone', 'fris en modern'),
            'Kleurwens: ' . $clean('colors', 'geen voorkeur'),
            '',
            'Regels: de hoofdkleur moet werken op facturen, knoppen en een website; de accentkleur is donker en leesbaar als tekstkleur; taglines en motivatie in het ' . self::language() . '; logo_text bij een monogram 1–3 hoofdletters, bij een woordmerk de bedrijfsnaam of een korte variant.',
        ]);
    }

    /** De taal van de markt, als Nederlandse taalnaam voor in de prompt (het model schrijft dan in die taal). */
    private static function language(): string
    {
        return \App\Support\Market::isPl() ? 'Pools' : 'Nederlands';
    }
}
