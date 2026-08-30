<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Vertaalbestanden lopen gelijk: elke Poolse sleutel heeft een Engelse
 * tegenhanger (en andersom), zodat de PL/EN-schakelaar in Lopra Polska
 * nooit half-vertaalde schermen oplevert. Ook de documentteksten (doc.php)
 * moeten in nl, en en pl dezelfde sleutels hebben.
 */
class LangParityTest extends TestCase
{
    /** @return array<string, string> sleutel => vertaling, samengevoegd uit alle json-bestanden van een taal */
    private function jsDictionary(string $locale): array
    {
        $merged = [];
        foreach (glob(resource_path("js/lang/{$locale}/*.json")) as $file) {
            $data = json_decode(file_get_contents($file), true);
            $this->assertIsArray($data, "Ongeldige JSON: {$file}");
            $merged = array_merge($merged, $data);
        }

        return $merged;
    }

    public function test_english_ui_dictionaries_cover_every_polish_key(): void
    {
        $pl = $this->jsDictionary('pl');
        $en = $this->jsDictionary('en');

        $this->assertNotEmpty($pl);
        $this->assertSame([], array_values(array_diff(array_keys($pl), array_keys($en))), 'Poolse UI-sleutels zonder Engelse vertaling');
        $this->assertSame([], array_values(array_diff(array_keys($en), array_keys($pl))), 'Engelse UI-sleutels zonder Poolse tegenhanger');

        foreach ($en as $key => $value) {
            $this->assertIsString($value, "Geen tekst voor '{$key}'");
            $this->assertNotSame('', trim($value), "Lege Engelse vertaling voor '{$key}'");
        }
    }

    public function test_english_server_dictionary_covers_every_polish_key(): void
    {
        $pl = json_decode(file_get_contents(lang_path('pl.json')), true);
        $en = json_decode(file_get_contents(lang_path('en.json')), true);

        $this->assertIsArray($pl);
        $this->assertIsArray($en);
        $this->assertSame([], array_values(array_diff(array_keys($pl), array_keys($en))), 'Poolse serversleutels zonder Engelse vertaling');
        $this->assertSame([], array_values(array_diff(array_keys($en), array_keys($pl))), 'Engelse serversleutels zonder Poolse tegenhanger');
    }

    public function test_placeholders_survive_translation(): void
    {
        // Laravel-plaatsvervangers (:name) en sjabloonvariabelen ({klant}) moeten in elke taal terugkomen.
        $tokens = fn (string $s) => collect(preg_split('/\s+/', $s))->filter(fn ($w) => preg_match('/^:[a-z_]+$|^\{[a-z_]+\}$/i', trim($w, '.,;:!?()')))->map(fn ($w) => trim($w, '.,;:!?()'))->sort()->values()->all();

        foreach (['js' => [$this->jsDictionary('pl'), $this->jsDictionary('en')], 'php' => [json_decode(file_get_contents(lang_path('pl.json')), true), json_decode(file_get_contents(lang_path('en.json')), true)]] as $kind => [$pl, $en]) {
            foreach ($pl as $key => $polish) {
                if (! isset($en[$key])) {
                    continue; // gedekt door de paritätstests hierboven
                }
                $expected = $tokens($key);
                if ($expected === []) {
                    continue;
                }
                $this->assertSame($expected, $tokens($en[$key]), "[{$kind}] plaatsvervangers verschillen voor '{$key}'");
            }
        }
    }

    public function test_document_dictionaries_have_the_same_keys(): void
    {
        $nl = array_keys(require lang_path('nl/doc.php'));
        $en = array_keys(require lang_path('en/doc.php'));
        $pl = array_keys(require lang_path('pl/doc.php'));

        $this->assertSame([], array_values(array_diff($nl, $en)), 'doc.php: nl-sleutels zonder en');
        $this->assertSame([], array_values(array_diff($pl, $en)), 'doc.php: pl-sleutels zonder en');
        $this->assertSame([], array_values(array_diff($en, $nl, $pl)), 'doc.php: en-sleutels die nl noch pl kent');
    }
}
