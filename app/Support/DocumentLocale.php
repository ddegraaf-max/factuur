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
    public const SUPPORTED = ['nl', 'en'];

    public static function using(?string $language, \Closure $callback): mixed
    {
        $language = in_array($language, self::SUPPORTED, true) ? $language : 'nl';

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
