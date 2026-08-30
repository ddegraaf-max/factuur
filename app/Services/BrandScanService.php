<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Support\Facades\Log;

/**
 * Huisstijl herkennen met AI: upload een huisstijlgids, briefpapier of oude
 * factuur (PDF of afbeelding) en Claude leest er de merkkleur, accentkleur,
 * lettertype-stijl en het best passende factuurtemplate uit. De gebruiker
 * ziet het voorstel in het huisstijlscherm en bevestigt zelf.
 *
 * Zelfde aan/uit-schakelaar als de andere AI-functies (ANTHROPIC_API_KEY).
 */
class BrandScanService
{
    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function availableFor(?\App\Models\Company $company): bool
    {
        return $this->enabled() && $company !== null && $company->hasAiAccess();
    }

    /**
     * @return array{brand_color: string, accent_color: ?string, font: string, template: string, motivation: ?string}
     *
     * @throws \DomainException met een nette Nederlandse melding voor de gebruiker
     */
    public function scan(string $bytes, string $mimeType): array
    {
        if (! $this->enabled()) {
            throw new \DomainException(__('De AI-herkenning is niet geconfigureerd.'));
        }

        set_time_limit(180);

        $client = new Client(apiKey: config('services.anthropic.key'));

        $fileBlock = $mimeType === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => base64_encode($bytes)]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $mimeType, 'data' => base64_encode($bytes)]];

        $model = (string) config('services.anthropic.model');
        $params = [
            'model' => $model,
            'maxTokens' => 8000,
            'outputConfig' => [
                'format' => $this->outputFormat(),
                'effort' => 'medium',
            ],
            'messages' => [[
                'role' => 'user',
                'content' => [
                    $fileBlock,
                    ['type' => 'text', 'text' => $this->prompt()],
                ],
            ]],
            'requestOptions' => ['timeout' => 150],
        ];

        // De fallbacks-parameter bestaat alleen op Opus 5/Fable — andere
        // modellen (Sonnet, Haiku) geven er een 400 op.
        if (str_contains($model, 'opus-5') || str_contains($model, 'fable') || str_contains($model, 'mythos')) {
            $params['fallbacks'] = 'default';
            $params['betas'] = ['server-side-fallback-2026-07-01'];
        }

        try {
            $message = $client->beta->messages->create(...$params);
        } catch (\Anthropic\Core\Exceptions\AuthenticationException $e) {
            Log::error('Huisstijlherkenning: ongeldige Anthropic API-key', ['error' => $e->getMessage()]);
            throw new \DomainException(__('De AI-koppeling is verkeerd geconfigureerd (ongeldige API-key).'));
        } catch (\Anthropic\Core\Exceptions\RateLimitException) {
            throw new \DomainException(__('De AI-dienst is even druk. Probeer het over een minuut opnieuw.'));
        } catch (\Anthropic\Core\Exceptions\APIStatusException $e) {
            Log::warning('Huisstijlherkenning: API-fout', ['type' => $e->type?->value, 'error' => mb_substr($e->getMessage(), 0, 300)]);
            throw new \DomainException(__('Het document kon niet worden gelezen door een storing bij de AI-dienst. Probeer het zo opnieuw.'));
        } catch (\Anthropic\Core\Exceptions\APIConnectionException) {
            throw new \DomainException(__('Geen verbinding met de AI-dienst. Controleer de internetverbinding en probeer het opnieuw.'));
        }

        if ($message->stopReason === 'refusal') {
            throw new \DomainException(__('De AI wilde dit document niet verwerken. Stel de kleuren handmatig in.'));
        }
        if ($message->stopReason === 'max_tokens') {
            throw new \DomainException(__('Het document is te complex om automatisch te herkennen. Stel de kleuren handmatig in.'));
        }

        $json = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }

        if (! is_array($json)) {
            Log::warning('Huisstijlherkenning: onleesbaar antwoord van het model');
            throw new \DomainException(__('Het document kon niet worden gelezen. Probeer een scherpere afbeelding.'));
        }

        return $this->sanitize($json);
    }

    /* ===================== Intern ===================== */

    protected function outputFormat(): array
    {
        $nullable = fn (array $type) => ['anyOf' => [$type, ['type' => 'null']]];
        $hex = ['type' => 'string', 'pattern' => '^#[0-9A-Fa-f]{6}$'];

        return [
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'has_brand' => ['type' => 'boolean', 'description' => 'True als er een herkenbare huisstijl (kleuren/vormgeving) in het document zit.'],
                    'brand_color' => $nullable($hex + ['description' => 'De primaire merkkleur als hexcode (#RRGGBB). De meest kenmerkende, verzadigde kleur van de huisstijl — niet zwart, wit of grijs, tenzij de huisstijl écht monochroom is.']),
                    'accent_color' => $nullable($hex + ['description' => 'Een secundaire/accentkleur als hexcode, als die er duidelijk is. Anders null.']),
                    'font' => ['type' => 'string', 'enum' => ['sans', 'serif'], 'description' => 'Straalt de typografie een schreefloze (sans) of schreef- (serif) stijl uit?'],
                    'template' => ['type' => 'string', 'enum' => ['modern', 'classic', 'minimal'], 'description' => 'Welk factuurtemplate past het best: modern (kleurband, stevig), classic (formeel, lijnen/tabellen), minimal (veel witruimte, rustig)?'],
                    'motivation' => $nullable(['type' => 'string', 'description' => 'Eén korte zin in het ' . (\App\Support\Market::isPl() ? 'Pools' : 'Nederlands') . ': waarom deze kleuren en stijl.']),
                ],
                'required' => ['has_brand', 'brand_color', 'accent_color', 'font', 'template', 'motivation'],
                'additionalProperties' => false,
            ],
        ];
    }

    protected function prompt(): string
    {
        return <<<'PROMPT'
        Dit is een huisstijldocument van een ondernemer: een huisstijlgids,
        briefpapier, logo-ontwerp, website-screenshot of een bestaande factuur.
        Analyseer de visuele identiteit voor de factuurinstellingen.

        Regels:
        - brand_color is de meest kenmerkende merkkleur (verzadigd, geen
          zwart/wit/grijs tenzij de huisstijl echt monochroom is). Kies de
          kleur die op een factuur als hoofdkleur zou dienen.
        - accent_color alleen als er duidelijk een tweede kleur is.
        - font: oogt de typografie schreefloos (sans) of met schreven (serif)?
        - template: modern = krachtig met kleurband, classic = formeel met
          lijnen en tabellen, minimal = veel witruimte en rust. Kies wat het
          best bij de uitstraling past.
        - motivation: één korte zin, in dezelfde taal als de schema-omschrijving vraagt.
        - Zit er geen herkenbare huisstijl in het document, zet dan has_brand
          op false.
        PROMPT;
    }

    protected function sanitize(array $json): array
    {
        if (($json['has_brand'] ?? false) !== true) {
            throw new \DomainException(__('Er is geen herkenbare huisstijl gevonden in dit document. Probeer een huisstijlgids, briefpapier of een bestaande factuur.'));
        }

        $hex = function ($value): ?string {
            return (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', trim($value)))
                ? strtoupper(trim($value))
                : null;
        };

        $brand = $hex($json['brand_color'] ?? null);
        if ($brand === null) {
            throw new \DomainException(__('Er kon geen merkkleur worden herkend. Stel de kleur handmatig in.'));
        }

        return [
            'brand_color' => $brand,
            'accent_color' => $hex($json['accent_color'] ?? null),
            'font' => in_array($json['font'] ?? null, ['sans', 'serif'], true) ? $json['font'] : 'sans',
            'template' => in_array($json['template'] ?? null, ['modern', 'classic', 'minimal'], true) ? $json['template'] : 'modern',
            'motivation' => is_string($json['motivation'] ?? null) ? mb_substr(trim($json['motivation']), 0, 300) : null,
        ];
    }
}
