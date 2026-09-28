<?php

namespace App\Support;

/**
 * De omschrijving van een uitvraag netjes tonen: alinea's, opsommingen, en een
 * meegeplakte e-mailondertekening (alles na een regel "--") apart, zodat die
 * niet midden in de aanvraag staat.
 */
class TenderText
{
    /** @return array{body: string, signature: ?string} */
    public static function split(?string $text): array
    {
        // Mailprogramma's zetten achter de streepjes vaak een harde spatie (U+00A0);
        // die telt hier als gewone spatie, anders wordt de scheiding niet herkend.
        $text = str_replace(["\r\n", "\r", "\u{00A0}", "\u{202F}", "\u{2007}"], ["\n", "\n", ' ', ' ', ' '], (string) $text);
        // De gangbare scheiding voor een ondertekening: een regel met alleen "--".
        $parts = preg_split('/^[ \t]*--[ \t]*$/m', $text, 2);
        $signature = isset($parts[1]) ? trim(preg_replace("/\n{3,}/", "\n\n", $parts[1])) : '';

        return [
            'body' => trim($parts[0]),
            'signature' => $signature !== '' ? $signature : null,
        ];
    }

    public static function body(?string $text): string
    {
        return static::split($text)['body'];
    }

    /**
     * Alinea's en opsommingen. Regels die met "-", "•" of "*" beginnen vormen
     * samen een lijst; een lege regel begint een nieuwe alinea.
     *
     * @return array<int, array{type: 'p'|'ul', lines: array<int, string>}>
     */
    public static function blocks(?string $text): array
    {
        $blocks = [];
        $current = null;
        $flush = function () use (&$blocks, &$current) {
            if ($current && $current['lines']) {
                $blocks[] = $current;
            }
            $current = null;
        };

        foreach (explode("\n", static::body($text)) as $line) {
            $line = trim($line);
            if ($line === '') {
                $flush();
                continue;
            }
            $isItem = (bool) preg_match('/^[-•*]\s+(.*)$/u', $line, $m);
            $type = $isItem ? 'ul' : 'p';
            if (! $current || $current['type'] !== $type) {
                $flush();
                $current = ['type' => $type, 'lines' => []];
            }
            $current['lines'][] = $isItem ? $m[1] : $line;
        }
        $flush();

        return $blocks;
    }
}
