<?php

namespace App\Services;

use Anthropic\Client;
use App\Http\Controllers\PurchaseInvoiceController;
use Illuminate\Support\Facades\Log;

/**
 * Bonnetjes automatisch herkennen ("scan & herken"): een foto of PDF van een
 * bon of inkoopfactuur wordt door Claude (Anthropic) gelezen en omgezet naar
 * de velden van het inkoopformulier — leverancier, datum, factuurnummer en
 * de bedragen per BTW-tarief.
 *
 * Zonder ANTHROPIC_API_KEY blijft de scanknop verborgen; het handmatig
 * inboeken werkt altijd. De AI mag zich vergissen: de gebruiker ziet de bon
 * ernaast en controleert de ingevulde waarden voor het opslaan.
 */
class ReceiptScanService
{
    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * Mag dit bedrijf de scan gebruiken? Vereist een geconfigureerde API-key
     * én AI-toegang (Slim-abonnement, proefperiode, demo of vrijgesteld).
     */
    public function availableFor(?\App\Models\Company $company): bool
    {
        return $this->enabled() && $company !== null && $company->hasAiAccess();
    }

    /**
     * Herken een bon of factuur en geef de formuliervelden terug.
     *
     * @param  string  $bytes     ruwe bestandsinhoud
     * @param  string  $mimeType  application/pdf, image/png, image/jpeg of image/webp
     * @return array{supplier_name: ?string, supplier_reference: ?string, invoice_date: ?string, due_date: ?string, category: ?string, vat_lines: list<array{base: float, rate: float, vat: float}>, deductions: list<array{description: string, amount: float}>, total_incl: ?float, amount_due: ?float, notes: ?string, warning: ?string}
     *
     * @throws \DomainException met een nette Nederlandse melding voor de gebruiker
     */
    public function scan(string $bytes, string $mimeType): array
    {
        if (! $this->enabled()) {
            throw new \DomainException('Bonherkenning is niet geconfigureerd.');
        }

        // De AI-call kan even duren; ruim voor de standaard PHP-limiet van 30s.
        set_time_limit(180);

        $client = new Client(apiKey: config('services.anthropic.key'));

        $fileBlock = $mimeType === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => base64_encode($bytes)]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $mimeType, 'data' => base64_encode($bytes)]];

        try {
            $message = $client->beta->messages->create(
                model: config('services.anthropic.model'),
                maxTokens: 16000,
                outputConfig: [
                    'format' => $this->outputFormat(),
                    'effort' => 'medium',
                ],
                // Wijst een veiligheidsclassifier de aanvraag af, dan probeert
                // de API het zelf opnieuw op het aanbevolen terugvalmodel.
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                messages: [[
                    'role' => 'user',
                    'content' => [
                        $fileBlock,
                        ['type' => 'text', 'text' => $this->prompt()],
                    ],
                ]],
                requestOptions: ['timeout' => 150],
            );
        } catch (\Anthropic\Core\Exceptions\AuthenticationException $e) {
            Log::error('Bonherkenning: ongeldige Anthropic API-key', ['error' => $e->getMessage()]);
            throw new \DomainException('De AI-koppeling is verkeerd geconfigureerd (ongeldige API-key).');
        } catch (\Anthropic\Core\Exceptions\RateLimitException) {
            throw new \DomainException('De AI-dienst is even druk. Probeer het over een minuut opnieuw.');
        } catch (\Anthropic\Core\Exceptions\APIStatusException $e) {
            Log::warning('Bonherkenning: API-fout', ['type' => $e->type?->value, 'error' => mb_substr($e->getMessage(), 0, 300)]);
            throw new \DomainException('De bon kon niet worden gelezen door een storing bij de AI-dienst. Probeer het zo opnieuw.');
        } catch (\Anthropic\Core\Exceptions\APIConnectionException) {
            throw new \DomainException('Geen verbinding met de AI-dienst. Controleer de internetverbinding en probeer het opnieuw.');
        }

        if ($message->stopReason === 'refusal') {
            throw new \DomainException('De AI wilde dit document niet verwerken. Vul de gegevens handmatig in.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new \DomainException('Het document is te complex om automatisch te herkennen. Vul de gegevens handmatig in.');
        }

        $json = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }

        if (! is_array($json)) {
            Log::warning('Bonherkenning: onleesbaar antwoord van het model');
            throw new \DomainException('De bon kon niet worden gelezen. Probeer een scherpere foto.');
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
                    'is_invoice' => [
                        'type' => 'boolean',
                        'description' => 'True als het document een bon, kassabon of (inkoop)factuur is.',
                    ],
                    'supplier_name' => $nullable(['type' => 'string', 'description' => 'Naam van de leverancier/winkel zoals op het document.']),
                    'supplier_reference' => $nullable(['type' => 'string', 'description' => 'Factuurnummer of bonnummer van de leverancier.']),
                    'invoice_date' => $nullable(['type' => 'string', 'format' => 'date', 'description' => 'Factuur- of bondatum (YYYY-MM-DD).']),
                    'due_date' => $nullable(['type' => 'string', 'format' => 'date', 'description' => 'Vervaldatum, alleen als die expliciet vermeld staat.']),
                    'category' => $nullable(['type' => 'string', 'enum' => PurchaseInvoiceController::CATEGORIES, 'description' => 'Best passende kostencategorie.']),
                    'vat_lines' => [
                        'type' => 'array',
                        'description' => 'Bedragen gegroepeerd per BTW-tarief: één regel per tarief dat op het document voorkomt.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'base' => ['type' => 'number', 'description' => 'Grondslag exclusief BTW voor dit tarief.'],
                                'rate' => ['type' => 'number', 'enum' => [21, 9, 0], 'description' => 'BTW-tarief in procenten.'],
                                'vat' => ['type' => 'number', 'description' => 'BTW-bedrag voor dit tarief zoals op het document.'],
                            ],
                            'required' => ['base', 'rate', 'vat'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'total_incl' => $nullable(['type' => 'number', 'description' => 'Totaalbedrag inclusief BTW zoals vermeld op het document.']),
                    'deductions' => [
                        'type' => 'array',
                        'description' => 'Bedragen die volgens het document al zijn ontvangen, verrekend of in mindering gebracht op het te betalen bedrag (bijv. "reeds ontvangen", "betaald bij ons", door een deurwaarder ontvangen gelden, een aanbetaling of voorschot). GEEN kortingen — die horen in de grondslag. Leeg als er niets verrekend wordt.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'description' => ['type' => 'string', 'description' => 'Korte omschrijving, bijv. "Ontvangen door deurwaarder van debiteur".'],
                                'amount' => ['type' => 'number', 'description' => 'Het verrekende bedrag (positief getal).'],
                            ],
                            'required' => ['description', 'amount'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'amount_due' => $nullable(['type' => 'number', 'description' => 'Het bedrag dat volgens het document daadwerkelijk nog betaald moet worden ("door u te voldoen"), als dat expliciet vermeld staat.']),
                    'notes' => $nullable(['type' => 'string', 'description' => 'Korte omschrijving van de aankoop, één zin.']),
                ],
                'required' => ['is_invoice', 'supplier_name', 'supplier_reference', 'invoice_date', 'due_date', 'category', 'vat_lines', 'total_incl', 'deductions', 'amount_due', 'notes'],
                'additionalProperties' => false,
            ],
        ];
    }

    protected function prompt(): string
    {
        return <<<'PROMPT'
        Dit is een bon, kassabon of inkoopfactuur van een Nederlandse ondernemer.
        Haal de gegevens eruit voor de inkoopadministratie (crediteuren).

        Regels:
        - Groepeer de bedragen per BTW-tarief (21, 9 of 0): "base" is de grondslag
          exclusief BTW, "vat" het BTW-bedrag zoals het document het vermeldt.
          Staat er alleen een totaal inclusief BTW, reken dan terug met het tarief.
        - Gebruik tarief 0 ook voor vrijgestelde, verlegde of buitenlandse BTW.
        - Kortingen en statiegeld verwerk je in de grondslag, niet als aparte regel.
        - Vermeldt het document dat er al een bedrag is ontvangen of verrekend
          (bijv. "reeds ontvangen", "betaald bij ons", "in mindering", door een
          deurwaarder ontvangen gelden, of een aanbetaling)? Zet dat dan in
          "deductions" met een korte omschrijving. Zulke ontvangsten horen NIET
          in de vat_lines: de kosten en BTW blijven daar volledig staan.
        - Staat er expliciet een nog te betalen bedrag ("door u te voldoen"),
          vul dan amount_due in.
        - Datums als YYYY-MM-DD. Nederlandse notatie zoals "3 aug 2026" of
          "03-08-2026" betekent 2026-08-03 (dag-maand-jaar).
        - Wat niet op het document staat of onleesbaar is: null. Verzin niets.
        - Is het document geen bon of factuur, zet dan is_invoice op false.
        PROMPT;
    }

    /** Maak van de modeloutput veilige, gevalideerde formulierwaarden. */
    protected function sanitize(array $json): array
    {
        if (($json['is_invoice'] ?? false) !== true) {
            throw new \DomainException('Dit lijkt geen bon of factuur te zijn. Probeer een andere foto of vul de gegevens handmatig in.');
        }

        $text = function ($value, int $max): ?string {
            $value = is_string($value) ? trim($value) : null;

            return ($value === null || $value === '') ? null : mb_substr($value, 0, $max);
        };

        $date = function ($value): ?string {
            if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
                return null;
            }

            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
        };

        $lines = [];
        foreach (is_array($json['vat_lines'] ?? null) ? $json['vat_lines'] : [] as $line) {
            $base = round((float) ($line['base'] ?? 0), 2);
            $vat = round((float) ($line['vat'] ?? 0), 2);
            $rate = (float) ($line['rate'] ?? 0);
            if (! in_array($rate, [0.0, 9.0, 21.0], true) || ($base === 0.0 && $vat === 0.0)) {
                continue;
            }
            $lines[] = ['base' => $base, 'rate' => $rate, 'vat' => $vat];
        }

        if ($lines === []) {
            throw new \DomainException('Er zijn geen bedragen herkend op het document. Probeer een scherpere foto of vul ze handmatig in.');
        }

        $category = $text($json['category'] ?? null, 60);
        if ($category !== null && ! in_array($category, PurchaseInvoiceController::CATEGORIES, true)) {
            $category = null;
        }

        // Verrekeningen: al ontvangen/ingehouden bedragen van het document.
        $deductions = [];
        foreach (is_array($json['deductions'] ?? null) ? $json['deductions'] : [] as $d) {
            $amount = round((float) ($d['amount'] ?? 0), 2);
            $label = $text($d['description'] ?? null, 190);
            if ($amount > 0) {
                $deductions[] = ['description' => $label ?? 'Reeds ontvangen/verrekend', 'amount' => $amount];
            }
        }

        // Controle: komt de som van de regels overeen met het totaal op de bon?
        $warning = null;
        $totalIncl = isset($json['total_incl']) && is_numeric($json['total_incl']) ? round((float) $json['total_incl'], 2) : null;
        if ($totalIncl !== null) {
            $sum = round(array_sum(array_map(fn ($l) => $l['base'] + $l['vat'], $lines)), 2);
            if (abs($sum - $totalIncl) > 0.05) {
                $warning = sprintf(
                    'De herkende regels tellen op tot € %s, maar de bon vermeldt € %s — controleer de bedragen.',
                    number_format($sum, 2, ',', '.'),
                    number_format($totalIncl, 2, ',', '.')
                );
            }
        }

        // Tweede controle: klopt "te betalen" met totaal minus verrekeningen?
        $amountDue = isset($json['amount_due']) && is_numeric($json['amount_due']) ? round((float) $json['amount_due'], 2) : null;
        if ($warning === null && $amountDue !== null) {
            $sum = round(array_sum(array_map(fn ($l) => $l['base'] + $l['vat'], $lines)), 2);
            $payable = round($sum - array_sum(array_column($deductions, 'amount')), 2);
            if (abs($payable - $amountDue) > 0.05) {
                $warning = sprintf(
                    'Volgens de herkende bedragen is er € %s te betalen, maar het document vermeldt € %s — controleer de bedragen en verrekeningen.',
                    number_format($payable, 2, ',', '.'),
                    number_format($amountDue, 2, ',', '.')
                );
            }
        }

        return [
            'supplier_name' => $text($json['supplier_name'] ?? null, 180),
            'supplier_reference' => $text($json['supplier_reference'] ?? null, 100),
            'invoice_date' => $date($json['invoice_date'] ?? null),
            'due_date' => $date($json['due_date'] ?? null),
            'category' => $category,
            'vat_lines' => $lines,
            'deductions' => $deductions,
            'total_incl' => $totalIncl,
            'amount_due' => $amountDue,
            'notes' => $text($json['notes'] ?? null, 500),
            'warning' => $warning,
        ];
    }
}
