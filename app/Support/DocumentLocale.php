<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Voert een stukje werk (PDF renderen, mail versturen) uit in de taal van
 * het document en herstelt daarna de oorspronkelijke taal. De app zelf
 * blijft Nederlands; alleen klantdocumenten volgen de klanttaal.
 */
class DocumentLocale
{
    public const SUPPORTED = ['nl', 'en', 'pl'];

    /** Standaardtaal van documenten: die van de markt (nl in Nederland, pl in Polen). */
    public static function default(): string
    {
        $locale = Market::locale();

        return in_array($locale, self::SUPPORTED, true) ? $locale : 'nl';
    }

    /**
     * CSS-lettertype voor PDF's. De ingebouwde Times/Courier van DomPDF kennen
     * alleen West-Europese tekens: Poolse letters (ł, ś, ą, ę, ż) worden daar
     * vraagtekens. De meegeleverde DejaVu-fonts hebben ze wel, dus Poolse
     * documenten krijgen DejaVu Serif / Sans Mono; sans is altijd DejaVu Sans.
     * Let op: in Blade met {!! !!} uitvoeren — met {{ }} worden de aanhalings-
     * tekens HTML-escaped en negeert DomPDF het hele lettertype.
     */
    public static function font(string $kind): string
    {
        $extended = app()->getLocale() === 'pl';

        return match ($kind) {
            'serif' => $extended ? "'DejaVu Serif', serif" : 'Georgia, serif',
            'mono' => $extended ? "'DejaVu Sans Mono', monospace" : "'Courier', monospace",
            default => "'DejaVu Sans', sans-serif",
        };
    }

    /**
     * Label bij het registratienummer van een bedrijf. Dat hoort bij het land
     * van dát bedrijf, niet bij de taal van het document: een Nederlands bedrijf
     * heeft ook op een Poolse factuur een KVK-nummer, geen REGON.
     */
    public static function registryLabel(?string $country): string
    {
        return match (strtoupper((string) $country)) {
            'NL' => 'KVK',
            'PL' => 'REGON',
            default => __('doc.coc'),
        };
    }

    /**
     * Vertaalde voetnoten uit een instellingenformulier: alleen ondersteunde
     * talen, zonder lege teksten; null als er niets overblijft.
     */
    public static function cleanFooters(mixed $input): ?array
    {
        if (! is_array($input)) {
            return null;
        }

        $clean = [];
        foreach (self::SUPPORTED as $language) {
            $text = trim((string) ($input[$language] ?? ''));
            if ($text !== '') {
                $clean[$language] = mb_substr($text, 0, 1000);
            }
        }

        return $clean ?: null;
    }

    public static function using(?string $language, \Closure $callback): mixed
    {
        $language = in_array($language, self::SUPPORTED, true) ? $language : self::default();

        $previousApp = app()->getLocale();
        $previousCarbon = Carbon::getLocale();

        app()->setLocale($language);
        Carbon::setLocale($language); // voor translatedFormat() in de sjablonen

        try {
            return $callback();
        } finally {
            app()->setLocale($previousApp);
            Carbon::setLocale($previousCarbon);
        }
    }
}
