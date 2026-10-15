<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Haalt een bestand op van een openbare link, voor de Claude-koppeling
 * (bijlage "url" bij het aanmaken en de tool bijlage_uploaden).
 *
 * De app praat hier namens een buitenstaander met het internet, dus:
 * alleen http(s), alleen openbare adressen (geen localhost, geen
 * privé-reeksen — ook niet via een doorverwijzing), een harde grens
 * van 10 MB en korte time-outs.
 */
class RemoteFile
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    protected const MAX_HOPS = 5;

    /**
     * Adresopzoeker (hostnaam → IP-adressen). Tests vervangen hem, zodat er
     * geen echte DNS nodig is.
     *
     * @var null|callable(string): array<int, string>
     */
    public static $resolver = null;

    /**
     * @return array{0: string, 1: ?string} de inhoud, en de bestandsnaam uit de bron (Content-Disposition of het pad) als die er is
     */
    public static function fetch(string $url): array
    {
        $url = trim($url);

        for ($hop = 0; $hop < self::MAX_HOPS; $hop++) {
            self::assertPublic($url);

            $response = Http::withOptions(['allow_redirects' => false, 'stream' => true])
                ->connectTimeout(10)
                ->timeout(30)
                ->withHeaders(['User-Agent' => Brand::name() . ' bijlage-ophaler', 'Accept' => '*/*'])
                ->get($url);

            if ($response->redirect()) {
                $next = (string) $response->header('Location');
                if ($next === '') {
                    throw new \DomainException(__('De link verwijst door zonder doel.'));
                }
                $url = self::absolute($url, $next);

                continue;
            }

            if (! $response->successful()) {
                throw new \DomainException(__('Het bestand kon niet worden opgehaald (HTTP :status). Controleer of de link openbaar is.', ['status' => $response->status()]));
            }

            if ((int) $response->header('Content-Length') > self::MAX_BYTES) {
                throw new \DomainException(__('De bijlage is groter dan 10 MB.'));
            }

            // In stukken lezen en afkappen: een Content-Length kan ontbreken of liegen.
            $body = $response->toPsrResponse()->getBody();
            $binary = '';
            while (! $body->eof()) {
                $binary .= $body->read(64 * 1024);
                if (strlen($binary) > self::MAX_BYTES) {
                    throw new \DomainException(__('De bijlage is groter dan 10 MB.'));
                }
            }

            return [$binary, self::filename($url, (string) $response->header('Content-Disposition'))];
        }

        throw new \DomainException(__('De link verwijst te vaak door.'));
    }

    /** Bestandsnaam uit de Content-Disposition-header, anders uit het pad van de URL. */
    protected static function filename(string $url, string $disposition): ?string
    {
        if (preg_match('/filename\*=(?:UTF-8\'\')?([^;]+)/i', $disposition, $m)) {
            return self::clean(rawurldecode(trim($m[1], " \"'")));
        }
        if (preg_match('/filename="?([^";]+)"?/i', $disposition, $m)) {
            return self::clean(trim($m[1]));
        }

        $base = basename((string) parse_url($url, PHP_URL_PATH));

        return str_contains($base, '.') ? self::clean(rawurldecode($base)) : null;
    }

    protected static function clean(string $name): ?string
    {
        $name = trim($name);

        return $name !== '' && $name !== '.' ? mb_substr($name, 0, 255) : null;
    }

    /** Alleen http(s) naar een openbaar adres; lokale en privé-reeksen zijn verboden. */
    protected static function assertPublic(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));

        if (! $parts || ! in_array($scheme, ['https', 'http'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new \DomainException(__('Geef een geldige, openbare https-link naar het bestand.'));
        }
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal') || str_ends_with($host, '.home.arpa')) {
            throw new \DomainException(__('Alleen openbare adressen zijn toegestaan.'));
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::resolve($host);
        if ($addresses === []) {
            throw new \DomainException(__('De host ":host" is niet te vinden.', ['host' => $host]));
        }
        foreach ($addresses as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new \DomainException(__('Alleen openbare adressen zijn toegestaan.'));
            }
        }
    }

    /** @return array<int, string> */
    protected static function resolve(string $host): array
    {
        if (self::$resolver) {
            return array_values((array) call_user_func(self::$resolver, $host));
        }

        $v4 = @gethostbynamel($host) ?: [];
        $v6 = array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6');

        return array_values(array_unique(array_merge($v4, $v6)));
    }

    /** Een relatieve Location-header omzetten naar een absolute URL. */
    protected static function absolute(string $base, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME)) {
            return $location;
        }

        $b = parse_url($base) ?: [];
        $scheme = $b['scheme'] ?? 'https';
        $origin = $scheme . '://' . ($b['host'] ?? '') . (isset($b['port']) ? ':' . $b['port'] : '');

        if (str_starts_with($location, '//')) {
            return $scheme . ':' . $location;
        }
        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }

        $dir = rtrim(str_replace('\\', '/', dirname($b['path'] ?? '/')), '/');

        return $origin . $dir . '/' . $location;
    }
}
