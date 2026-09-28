<?php

namespace App\Support;

/**
 * Title en description op een lengte die zoekmachines heel laten: een title tot
 * 65 tekens, een description tot 165. Voor pagina's waarvan de tekst uit een
 * artikel komt (kennisbank, helpcentrum) en dus niet met de hand is afgemeten.
 */
class Seo
{
    public const TITLE_MAX = 65;

    public const DESCRIPTION_MAX = 165;

    /**
     * De eerste schrijfwijze die past; geef ze van lang naar kort. Past er geen,
     * dan de laatste, afgekapt op een heel woord.
     */
    public static function title(string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            if (mb_strlen($candidate) <= self::TITLE_MAX) {
                return $candidate;
            }
        }

        return self::cut((string) end($candidates), self::TITLE_MAX);
    }

    public const DESCRIPTION_MIN = 70;

    /**
     * De tekst zelf als die past, anders zoveel hele zinnen als er passen, anders
     * hele woorden. Een te korte tekst krijgt de aanvulling erachter.
     */
    public static function description(string $text, string $supplement = ''): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($supplement !== '' && mb_strlen($text) < self::DESCRIPTION_MIN) {
            $text = trim($text . ' ' . $supplement);
        }
        if (mb_strlen($text) <= self::DESCRIPTION_MAX) {
            return $text;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];
        $fit = '';
        foreach ($sentences as $sentence) {
            if (mb_strlen(trim($fit . ' ' . $sentence)) > self::DESCRIPTION_MAX) {
                break;
            }
            $fit = trim($fit . ' ' . $sentence);
        }

        return mb_strlen($fit) >= self::DESCRIPTION_MIN ? $fit : self::cut($text, self::DESCRIPTION_MAX);
    }

    private static function cut(string $text, int $max): string
    {
        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space ? mb_substr($cut, 0, $space) : $cut, ' ,;:—-') . '…';
    }
}
