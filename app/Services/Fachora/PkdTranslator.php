<?php

namespace App\Services\Fachora;

use App\Models\Provider;
use App\Services\Ai\StructuredClaude;
use Illuminate\Support\Facades\Log;

/**
 * 1.100: de hoofdactiviteit uit CEIDG (pkd_main_name, Pools) in het Engels, voor klanten
 * die geen Pools lezen. Eén AI-vertaling per unieke naam (de namen herhalen zich sterk),
 * opgeslagen in providers.pkd_main_name_en; de Engelse pagina toont die, de Poolse de
 * oorspronkelijke (Provider::pkdMainName). Draait na elke import op aanvraag
 * (fachora:local-search) en elke tien minuten (fachora:pkd-translate) voor wat nog
 * ontbreekt. Zonder AI-sleutel blijft het Pools.
 */
class PkdTranslator
{
    public function __construct(private StructuredClaude $ai) {}

    /** Vertaalt de ontbrekende namen (max $budget unieke namen per keer). @return int aantal bijgewerkte bedrijven */
    public function fill(int $budget = 40): int
    {
        if (! $this->ai->enabled()) {
            return 0;
        }
        $names = Provider::query()->whereNotNull('pkd_main_name')->whereNull('pkd_main_name_en')
            ->distinct()->orderBy('pkd_main_name')->limit(max(1, $budget))->pluck('pkd_main_name')->all();
        if ($names === []) {
            return 0;
        }
        $updated = 0;
        foreach ($this->translate($names) as $pl => $en) {
            $updated += Provider::query()->where('pkd_main_name', $pl)->whereNull('pkd_main_name_en')->update(['pkd_main_name_en' => $en]);
        }

        return $updated;
    }

    /**
     * @param  list<string>  $names
     * @return array<string, string> Poolse naam => Engelse naam (alleen wat gelukt is)
     */
    public function translate(array $names): array
    {
        $names = array_values(array_unique(array_filter(array_map('strval', $names))));
        if ($names === []) {
            return [];
        }
        $schema = [
            'type' => 'object',
            'properties' => ['translations' => ['type' => 'array', 'items' => [
                'type' => 'object',
                'properties' => ['pl' => ['type' => 'string'], 'en' => ['type' => 'string']],
                'required' => ['pl', 'en'],
                'additionalProperties' => false,
            ]]],
            'required' => ['translations'],
            'additionalProperties' => false,
        ];
        $prompt = "Translate these Polish PKD business-activity names (Polish Classification of Activities, as shown in the CEIDG register) into concise, natural English that a customer looking for a tradesperson understands. Keep the meaning, drop legal filler, no codes, no quotes, at most 90 characters each. Example: \"Wykonywanie instalacji wodno-kanalizacyjnych, cieplnych, gazowych i klimatyzacyjnych\" -> \"Plumbing, heating, gas and air-conditioning installation\".\n\n"
            . "Return JSON {translations: [{pl, en}]} with exactly one entry per input name, pl copied verbatim.\n\nNames:\n"
            . implode("\n", array_map(fn (string $n) => '- ' . $n, $names));
        try {
            $result = $this->ai->json($prompt, $schema, 2500, 'low', [], 'Fachora PKD-vertaling');
        } catch (\DomainException $e) {
            Log::warning('Fachora: PKD-vertaling mislukt', ['error' => $e->getMessage()]);

            return [];
        }
        $out = [];
        foreach ((array) ($result['translations'] ?? []) as $row) {
            $pl = trim((string) ($row['pl'] ?? ''));
            $en = trim((string) ($row['en'] ?? ''));
            if ($pl !== '' && $en !== '' && in_array($pl, $names, true)) {
                $out[$pl] = mb_substr($en, 0, 200);
            }
        }

        return $out;
    }
}
