<?php

namespace App\Support;

/**
 * IndexNow: zoekmachines (Bing, Yandex, Seznam, Naver en partners) direct
 * vertellen welke pagina's bestaan of gewijzigd zijn, in plaats van wachten
 * tot ze langskomen. Google doet niet mee; daar blijven sitemap, lastmod en
 * links de weg.
 *
 * De sleutel is afgeleid van APP_KEY, zodat per omgeving (EasyInvoice,
 * Lopra, Lopra Polska) automatisch een eigen sleutel bestaat zonder extra
 * instelling. Het sleutelbestand /{sleutel}.txt bewijst dat wij de site zijn.
 */
class IndexNow
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public static function key(): string
    {
        return substr(hash('sha256', 'indexnow|' . (string) config('app.key')), 0, 32);
    }

    public static function keyUrl(): string
    {
        return Brand::url(self::key() . '.txt');
    }

    public static function host(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
