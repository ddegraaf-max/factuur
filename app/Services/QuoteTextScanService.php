<?php

namespace App\Services;

use Anthropic\Client;
use Illuminate\Support\Facades\Log;

/**
 * Offerte uit tekst: een geplakte offertetekst (bijv. geschreven met Claude
 * of ChatGPT, of uit een e-mail) wordt door de AI omgezet naar de velden van
 * het offerteformulier — klant, regels, intro en voorwaarden.
 *
 * Zelfde aan/uit-schakelaar als de bonherkenning: zonder ANTHROPIC_API_KEY
 * blijft de functie verborgen. De gebruiker controleert het resultaat altijd
 * in het formulier voordat er iets wordt opgeslagen.
 */
class QuoteTextScanService
{
    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * Zet een offertetekst om naar formuliervelden.
     *
     * @return array{customer_name: ?string, reference: ?string, intro: ?string, notes: ?string, valid_days: ?int, lines: list<array{description: string, details: ?string, quantity: float, unit: string, unit_price: float, vat_rate: float, discount_pct: float}>, warning: ?string}
     *
     * @throws \DomainException met een nette Nederlandse melding voor de gebruiker
     */
    public function scan(string $text): array
    {
        if (! $this->enabled()) {
            throw new \DomainException('De AI-herkenning is niet geconfigureerd.');
        }

        set_time_limit(180);

        $client = new Client(apiKey: config('services.anthropic.key'));

        try {
            $message = $client->beta->messages->create(
                model: config('services.anthropic.model'),
                maxTokens: 16000,
                outputConfig: [
                    'format' => $this->outputFormat(),
                    'effort' => 'medium',
                ],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                messages: [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $this->prompt()],
                        ['type' => 'text', 'text' => "=== OFFERTETEKST ===\n" . $text],
                    ],
                ]],
                requestOptions: ['timeout' => 150],
            );
        } catch (\Anthropic\Core\Exceptions\AuthenticationException $e) {
            Log::error('Offerteherkenning: ongeldige Anthropic API-key', ['error' => $e->getMessage()]);
            throw new \DomainException('De AI-koppeling is verkeerd geconfigureerd (ongeldige API-key).');
        } catch (\Anthropic\Core\Exceptions\RateLimitException) {
            throw new \DomainException('De AI-dienst is even druk. Probeer het over een minuut opnieuw.');
        } catch (\Anthropic\Core\Exceptions\APIStatusException $e) {
            Log::warning('Offerteherkenning: API-fout', ['type' => $e->type?->value, 'error' => mb_substr($e->getMessage(), 0, 300)]);
            throw new \DomainException('De tekst kon niet worden gelezen door een storing bij de AI-dienst. Probeer het zo opnieuw.');
        } catch (\Anthropic\Core\Exceptions\APIConnectionException) {
            throw new \DomainException('Geen verbinding met de AI-dienst. Controleer de internetverbinding en probeer het opnieuw.');
        }

        if ($message->stopReason === 'refusal') {
            throw new \DomainException('De AI wilde deze tekst niet verwerken. Vul het formulier handmatig in.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new \DomainException('De tekst is te lang of te complex om automatisch te herkennen. Vul het formulier handmatig in.');
        }

        $json = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }

        if (! is_array($json)) {
            Log::warning('Offerteherkenning: onleesbaar antwoord van het model');
            throw new \DomainException('De tekst kon niet worden gelezen. Probeer het opnieuw.');
        }

        return $this->sanitize($json);
    }

    /* ===================== Intern ===================== */

    /** Structured output: het model MOET dit JSON-schema teruggeven. */
    protected function outputFormat(): array
    {
        $nullable = fn (array $type) => ['anyOf' => [$type, ['type' => 'null']]];

        return [
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'is_quote' => [
                        'type' => 'boolean',
                        'description' => 'True als de tekst een offerte, prijsvoorstel of prijsopgave is (of daar duidelijk voor bedoeld is).',
                    ],
                    'customer_name' => $nullable(['type' => 'string', 'description' => 'Naam van de klant/het bedrijf aan wie de offerte is gericht.']),
                    'reference' => $nullable(['type' => 'string', 'description' => 'Referentie of projectnaam, als die genoemd wordt.']),
                    'intro' => $nullable(['type' => 'string', 'description' => 'De inleiding van de offerte (aanhef/openingstekst), letterlijk of licht ingekort uit de tekst. Geen verzonnen tekst.']),
                    'notes' => $nullable(['type' => 'string', 'description' => 'Voorwaarden, planning, aannames of afsluitende opmerkingen uit de tekst.']),
                    'valid_days' => $nullable(['type' => 'integer', 'description' => 'Geldigheid van de offerte in dagen, alleen als de tekst die noemt.']),
                    'lines' => [
                        'type' => 'array',
                        'description' => 'De offerteregels: elk te leveren product of dienst met aantal en prijs.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'description' => ['type' => 'string', 'description' => 'Korte omschrijving van de regel.'],
                                'details' => $nullable(['type' => 'string', 'description' => 'Eventuele toelichting bij de regel.']),
                                'quantity' => ['type' => 'number', 'description' => 'Aantal (standaard 1).'],
                                'unit' => ['type' => 'string', 'description' => 'Eenheid, bijv. stuk, uur, dag, maand, m2 (standaard "stuk").'],
                                'unit_price' => ['type' => 'number', 'description' => 'Stuksprijs EXCLUSIEF btw.'],
                                'vat_rate' => ['type' => 'number', 'enum' => [21, 9, 0], 'description' => 'BTW-tarief in procenten.'],
                                'discount_pct' => $nullable(['type' => 'number', 'description' => 'Korting op deze regel in procenten (0-100).']),
                            ],
                            'required' => ['description', 'details', 'quantity', 'unit', 'unit_price', 'vat_rate', 'discount_pct'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'total_excl' => $nullable(['type' => 'number', 'description' => 'Totaalbedrag exclusief btw zoals de tekst het noemt, als dat er staat.']),
                    'total_incl' => $nullable(['type' => 'number', 'description' => 'Totaalbedrag inclusief btw zoals de tekst het noemt, als dat er staat.']),
                ],
                'required' => ['is_quote', 'customer_name', 'reference', 'intro', 'notes', 'valid_days', 'lines', 'total_excl', 'total_incl'],
                'additionalProperties' => false,
            ],
        ];
    }

    protected function prompt(): string
    {
        return <<<'PROMPT'
        Hieronder staat de tekst van een offerte of prijsvoorstel van een
        Nederlandse ondernemer (bijv. geschreven met een AI-assistent of in een
        e-mail). Zet de tekst om naar de velden van het offerteformulier.

        Regels:
        - Elk te leveren product of elke dienst wordt één offerteregel: korte
          omschrijving, eventuele toelichting in details, aantal, eenheid
          (stuk/uur/dag/maand/m2), stuksprijs EXCLUSIEF btw en btw-tarief.
        - Staan prijzen inclusief btw, reken dan terug naar exclusief. Noemt de
          tekst geen btw, ga dan uit van prijzen exclusief btw en tarief 21.
        - Een korting zet je als discount_pct op de regel(s) waar hij bij hoort;
          een korting op het totaal zet je als discount_pct op elke regel.
          Prijzen zijn nooit negatief.
        - intro is de openings-/inleidingstekst uit de offerte zelf, notes zijn
          de voorwaarden, planning of aannames. Neem de eigen woorden van de
          tekst over; verzin niets en voeg niets toe.
        - valid_days alleen invullen als de tekst een geldigheid noemt.
        - Wat niet in de tekst staat: null.
        - Is de tekst geen offerte of prijsvoorstel, zet dan is_quote op false.
        PROMPT;
    }

    /** Maak van de modeloutput veilige, gevalideerde formulierwaarden. */
    protected function sanitize(array $json): array
    {
        if (($json['is_quote'] ?? false) !== true) {
            throw new \DomainException('Dit lijkt geen offertetekst te zijn. Plak de volledige offerte, of vul het formulier handmatig in.');
        }

        $text = function ($value, int $max): ?string {
            $value = is_string($value) ? trim($value) : null;

            return ($value === null || $value === '') ? null : mb_substr($value, 0, $max);
        };

        $lines = [];
        foreach (is_array($json['lines'] ?? null) ? $json['lines'] : [] as $line) {
            $description = $text($line['description'] ?? null, 500);
            if ($description === null) {
                continue;
            }
            $rate = (float) ($line['vat_rate'] ?? 21);
            if (! in_array($rate, [0.0, 9.0, 21.0], true)) {
                $rate = 21.0;
            }
            $quantity = round((float) ($line['quantity'] ?? 1), 3);
            $lines[] = [
                'description' => $description,
                'details' => $text($line['details'] ?? null, 2000),
                'quantity' => $quantity > 0 ? $quantity : 1.0,
                'unit' => $text($line['unit'] ?? null, 30) ?? 'stuk',
                'unit_price' => max(0, round((float) ($line['unit_price'] ?? 0), 2)),
                'vat_rate' => $rate,
                'discount_pct' => min(100, max(0, round((float) ($line['discount_pct'] ?? 0), 2))),
            ];
        }

        if ($lines === []) {
            throw new \DomainException('Er zijn geen offerteregels herkend in de tekst. Controleer of de prijzen erin staan, of vul het formulier handmatig in.');
        }

        // Controle: kloppen de herkende regels met het totaal dat de tekst noemt?
        $warning = null;
        $sumExcl = round(array_sum(array_map(
            fn ($l) => $l['quantity'] * $l['unit_price'] * (1 - $l['discount_pct'] / 100),
            $lines
        )), 2);
        $totalExcl = isset($json['total_excl']) && is_numeric($json['total_excl']) ? round((float) $json['total_excl'], 2) : null;
        if ($totalExcl !== null && abs($sumExcl - $totalExcl) > 0.05) {
            $warning = sprintf(
                'De herkende regels tellen op tot € %s excl. btw, maar de tekst noemt € %s — controleer de bedragen.',
                number_format($sumExcl, 2, ',', '.'),
                number_format($totalExcl, 2, ',', '.')
            );
        }

        return [
            'customer_name' => $text($json['customer_name'] ?? null, 180),
            'reference' => $text($json['reference'] ?? null, 255),
            'intro' => $text($json['intro'] ?? null, 2000),
            'notes' => $text($json['notes'] ?? null, 2000),
            'valid_days' => is_numeric($json['valid_days'] ?? null) ? max(1, min(365, (int) $json['valid_days'])) : null,
            'lines' => $lines,
            'warning' => $warning,
        ];
    }
}
