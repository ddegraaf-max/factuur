<?php

namespace App\Services\Checks;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Het Centraal Insolventieregister van de Rechtspraak: faillissementen,
 * surseances en schuldsaneringen, van bedrijven én personen. De webservice is
 * gratis na aanmelden (insolventies.rechtspraak.nl, registratie); de
 * gebruikersnaam en het wachtwoord staan in CIR_USERNAME en CIR_PASSWORD.
 * Zonder die twee doet deze bron niets.
 *
 * SOAP 1.2 met de gebruikersnaam en het wachtwoord in de kop (WS-Security).
 * Zoeken geeft publicatiekenmerken; per kenmerk haalt getCase de zaak op.
 */
class InsolvencyService
{
    private const NS = 'http://www.rechtspraak.nl/namespaces/cir01';

    /** Hooguit zoveel zaken per zoekopdracht ophalen; meer zegt niets extra. */
    private const MAX_CASES = 5;

    public function configured(): bool
    {
        return filled(config('services.cir.username')) && filled(config('services.cir.password'));
    }

    /**
     * Een bedrijf: op KvK-nummer, of anders op naam met postcode en huisnummer.
     *
     * @return array{cases: array<int, array<string, mixed>>, checked_at: string}|null null als er niet gezocht kon worden
     */
    public function forCompany(?string $kvkNumber, ?string $name = null, ?string $postalCode = null, ?string $houseNumber = null, bool $fresh = false): ?array
    {
        $kvk = preg_replace('/\D/', '', (string) $kvkNumber) ?? '';
        if (strlen($kvk) === 8) {
            return $this->lookup('searchUndertaking', ['commercialRegisterID' => $kvk], $fresh);
        }
        $postal = self::postalCode($postalCode);
        $house = self::houseNumber($houseNumber);
        if (filled($name) && $postal && $house) {
            return $this->lookup('searchUndertaking', ['name' => trim((string) $name), 'postalCode' => $postal, 'houseNumber' => $house], $fresh);
        }

        return null;
    }

    /**
     * Een persoon: achternaam met postcode en huisnummer (een geboortedatum
     * hebben we niet). Namen op hetzelfde adres kunnen samenvallen; de uitslag
     * is een aanwijzing om zelf na te kijken.
     */
    public function forPerson(?string $fullName, ?string $postalCode, ?string $houseNumber, bool $fresh = false): ?array
    {
        $parts = self::splitName($fullName);
        $postal = self::postalCode($postalCode);
        $house = self::houseNumber($houseNumber);
        if (! $parts || ! $postal || ! $house) {
            return null;
        }

        return $this->lookup('searchNaturalPerson', array_filter([
            'prefix' => $parts['prefix'],
            'surname' => $parts['surname'],
            'postalCode' => $postal,
            'houseNumber' => $house,
        ], fn ($v) => $v !== null && $v !== ''), $fresh);
    }

    /** "Jan de Vries" wordt voorvoegsel "de" en achternaam "Vries". */
    public static function splitName(?string $fullName): ?array
    {
        $words = preg_split('/\s+/', trim((string) $fullName)) ?: [];
        $words = array_values(array_filter($words, fn ($w) => $w !== ''));
        if (count($words) < 2) {
            return null;
        }
        $prefixes = ['van', 'de', 'der', 'den', 'het', 't', "'t", 'ten', 'ter', 'te', 'op', 'in', 'aan', 'bij', 'onder', 'over', 'uit', 'vd', 'v.d.', 'la', 'le', 'du', 'von', 'da', 'di', 'el', 'al'];
        $surname = array_pop($words);
        $prefix = [];
        while ($words && in_array(mb_strtolower(end($words)), $prefixes, true)) {
            array_unshift($prefix, array_pop($words));
        }
        if (! $words) {
            return null; // alleen een voorvoegsel en een achternaam: geen voornaam, dus waarschijnlijk een bedrijf
        }

        return ['prefix' => $prefix ? implode(' ', $prefix) : null, 'surname' => $surname];
    }

    private static function postalCode(?string $value): ?string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');

        return preg_match('/^\d{4}[A-Z]{2}$/', $clean) ? $clean : null;
    }

    private static function houseNumber(?string $value): ?int
    {
        return preg_match('/(\d+)/', (string) $value, $m) ? (int) $m[1] : null;
    }

    /** @param  array<string, string|int>  $params */
    private function lookup(string $operation, array $params, bool $fresh = false): ?array
    {
        if (! $this->configured()) {
            return null;
        }
        $key = 'cir:' . $operation . ':' . sha1(json_encode($params));
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addHours(24), function () use ($operation, $params) {
            $body = '<' . $operation . ' xmlns="' . self::NS . '">';
            foreach ($params as $name => $value) {
                $body .= '<' . $name . '>' . htmlspecialchars((string) $value, ENT_XML1) . '</' . $name . '>';
            }
            $body .= '</' . $operation . '>';

            $xml = $this->call($operation, $body);
            if ($xml === null) {
                return null;
            }
            preg_match_all('/<publicatieKenmerk>([^<]+)<\/publicatieKenmerk>/', $xml, $m);
            $cases = [];
            $seen = [];
            foreach (array_slice($m[1], 0, self::MAX_CASES) as $publication) {
                $case = $this->getCase(trim($publication));
                if ($case && ! isset($seen[$case['number']])) {
                    $seen[$case['number']] = true;
                    $cases[] = $case;
                }
            }

            return ['cases' => $cases, 'checked_at' => now()->toIso8601String()];
        });
    }

    /** @return array<string, mixed>|null */
    private function getCase(string $publication): ?array
    {
        $xml = $this->call('getCase', '<getCase xmlns="' . self::NS . '"><publicationNumber>' . htmlspecialchars($publication, ENT_XML1) . '</publicationNumber></getCase>');
        if ($xml === null || ! preg_match('/<inspubWebserviceInsolvente.*?<\/inspubWebserviceInsolvente>/s', $xml, $m)) {
            return null;
        }
        $doc = @simplexml_load_string(preg_replace('/\sxmlns="[^"]*"/', '', $m[0]) ?? '');
        if (! $doc || ! isset($doc->insolvente)) {
            return null;
        }
        $case = $doc->insolvente;
        $number = trim((string) $case->insolventienummer);
        $publications = [];
        foreach ($case->publicatiegeschiedenis->publicatie ?? [] as $pub) {
            $publications[] = ['date' => (string) $pub->publicatieDatum, 'description' => trim((string) $pub->publicatieOmschrijving)];
        }
        usort($publications, fn ($a, $b) => strcmp($b['date'], $a['date']));
        $latest = $publications[0] ?? null;
        $person = $case->persoon;
        $name = trim(implode(' ', array_filter([(string) ($person->voornaam ?? ''), (string) ($person->voorletters ?? ''), (string) ($person->voorvoegsel ?? ''), (string) ($person->achternaam ?? '')])));

        return [
            'number' => $number,
            // F = faillissement, S = surseance, R = schuldsanering.
            'type' => match (strtoupper(substr($number, 0, 1))) {
                'F' => 'faillissement',
                'S' => 'surseance',
                'R' => 'schuldsanering',
                default => 'insolventie',
            },
            'name' => $name ?: null,
            'kvk' => trim((string) ($person->KvKNummer ?? '')) ?: null,
            'court' => trim((string) $case->behandelendeInstantieNaam) ?: null,
            'latest' => $latest,
            // Een beëindigde zaak blijft nog zes maanden zichtbaar.
            'ended' => $latest ? (bool) preg_match('/be[eë]indig|opgeheven|opheffing|vernietig|einde|homologatie|intrekking|ingetrokken/iu', $latest['description']) : false,
        ];
    }

    /** Eén aanroep van de webservice; de ruwe XML, of null bij een storing. */
    private function call(string $operation, string $body): ?string
    {
        $envelope = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap12:Envelope xmlns:soap12="http://www.w3.org/2003/05/soap-envelope" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">'
            . '<soap12:Header><wsse:Security soap12:mustUnderstand="1"><wsse:UsernameToken>'
            . '<wsse:Username>' . htmlspecialchars((string) config('services.cir.username'), ENT_XML1) . '</wsse:Username>'
            . '<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars((string) config('services.cir.password'), ENT_XML1) . '</wsse:Password>'
            . '</wsse:UsernameToken></wsse:Security></soap12:Header>'
            . '<soap12:Body>' . $body . '</soap12:Body></soap12:Envelope>';

        try {
            $response = Http::withBody($envelope, 'application/soap+xml; charset=utf-8; action="' . self::NS . '/' . $operation . '"')
                ->timeout(15)
                ->post((string) config('services.cir.url'));
        } catch (\Throwable $e) {
            Log::warning('Insolventieregister niet bereikbaar', ['operation' => $operation, 'error' => $e->getMessage()]);

            return null;
        }

        $xml = $response->body();
        if (! $response->successful() || str_contains($xml, '<soap:Fault') || str_contains($xml, ':Fault>') || str_contains($xml, '<exceptie')) {
            Log::warning('Insolventieregister gaf een fout', ['operation' => $operation, 'status' => $response->status(), 'body' => mb_substr($xml, 0, 300)]);

            return null;
        }

        return $xml;
    }
}
