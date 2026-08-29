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
