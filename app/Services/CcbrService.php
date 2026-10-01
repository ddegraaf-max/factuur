<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Het Centraal Curatele- en Bewindregister van de Rechtspraak.
 *
 * ── Waarvoor dit bestaat ──────────────────────────────────────────────────
 *
 * Staat iemand onder bewind of curatele, dan kan hij zijn geldzaken niet zelf
 * regelen. Een vordering loopt dan via de bewindvoerder of curator, en een
 * aanmaning of incasso rechtstreeks aan de persoon is zinloos en soms
 * onrechtmatig. Dit register is de enige plek waar dat openbaar staat.
 *
 * ── Hoe de koppeling werkt ────────────────────────────────────────────────
 *
 * Drie stappen, zo voorgeschreven door de Rechtspraak:
 *
 *  1. Token ophalen bij een aparte secure token service (ADFS, WS-Trust 1.3)
 *     met gebruikersnaam en wachtwoord. Je krijgt een ondertekende SAML 1.1
 *     bearer-assertie die een uur geldig is.
 *  2. Zoeken op achternaam plus geboortedatum (of alleen het geboortejaar).
 *     Je krijgt nul of meer registerkaarten met een ondoorzichtige
 *     "aanduiding" per kaart.
 *  3. Met die aanduiding de kaart opvragen: de grond, de ingangsdatum, de
 *     rechtbank en wie de bewindvoerder of curator is.
 *
 * ── Drie dingen die niet vanzelf goed gaan ────────────────────────────────
 *
 * De assertie is **ondertekend**. Hij moet daarom letterlijk, teken voor
 * teken, in het volgende bericht worden geplakt. Opnieuw opbouwen via DOM
 * verandert de canonicalisatie en dan klopt de signatuur niet meer. Daarom
 * staat hier een string-knip en geen XML-manipulatie.
 *
 * De dataservice gebruikt `ccbr.rechtspraak.nl/v1` als namespace, zonder
 * schema ervoor. Dat is geen geldige URI en libxml geeft daar een
 * waarschuwing over bij elk antwoord. Die waarschuwingen worden hier
 * afgevangen, anders staat het logboek er vol mee.
 *
 * De server draait op een PKIoverheid-certificaat waarvan de stam in geen
 * enkele truststore zit (zie config/services.php). Zonder die stam mislukt
 * elke verbinding met "self-signed certificate in certificate chain".
 *
 * ── Wat je met de uitkomst mag doen ───────────────────────────────────────
 *
 * De gebruiksvoorwaarden staan één doel toe: handelspartijen informeren over
 * de curatele of het bewind. Verwerking voor een ander doel — een risicoscore,
 * profielverrijking, doorverkoop — is volgens artikel 2 onrechtmatig. Deze
 * klasse levert daarom alleen gegevens terug; ze rekent niets uit en voedt
 * bewust de klantscore niet.
 */
class CcbrService
{
    /**
     * De vier gronden die de Rechtspraak op de pagina "Zoekresultaten CCBR"
     * noemt, in die volgorde. De grond noemt de maatregel zelf, en dat is het
     * enige gedocumenteerde signaal dat onderscheid maakt tussen bewind en
     * curatele.
     *
     * `SoortRegister` uit het antwoord (waarden als "Item02") staat in geen
     * enkel schema of document beschreven, dus daar wordt hier geen label op
     * gebaseerd. Het kaartnummer begint volgens dezelfde pagina met 'CB' of
     * 'BM' en dient als kruiscontrole.
     */
    private const GRONDEN = [
        '01' => ['bewind', 'Bewind: verkwisting of problematische schulden'],
        '02' => ['bewind', 'Bewind: lichamelijke of geestelijke toestand'],
        '03' => ['curatele', 'Curatele: geestelijke stoornis'],
        '04' => ['curatele', 'Curatele: drank- of drugsmisbruik'],
    ];

    private const BERICHTEN_NS = 'ccbr.rechtspraak.nl/v1/CcbrDataservice/berichten';

    /** Is de koppeling ingesteld? Zonder inloggegevens bestaat de functie niet. */
    public function enabled(): bool
    {
        return filled(config('services.ccbr.username')) && filled(config('services.ccbr.password'));
    }

    /**
     * Zoekt op achternaam met een geboortedatum óf een geboortejaar.
     *
     * Het jaar alleen mag ook: dat is het geval waarin je de volledige datum
     * niet kent, en het register staat het toe. Je krijgt dan meer treffers.
     *
     * @return array{treffers: list<array<string, mixed>>, verificatiecode: ?string}
     */
    public function zoek(string $achternaam, ?string $geboortedatum = null, ?int $geboortejaar = null, string $voorvoegsel = ''): array
    {
        $achternaam = trim($achternaam);

        if ($achternaam === '') {
            throw new \DomainException(__('Vul een achternaam in.'));
        }

        if (! $geboortedatum && ! $geboortejaar) {
            throw new \DomainException(__('Vul een geboortedatum in, of in elk geval het geboortejaar.'));
        }

        $geboorte = $geboortedatum
            ? '<b:Datum>' . $this->xml(substr($geboortedatum, 0, 10)) . 'T00:00:00</b:Datum><b:Jaar i:nil="true"/>'
            : '<b:Datum i:nil="true"/><b:Jaar>' . (int) $geboortejaar . '</b:Jaar>';

        $body = '<ZoekRegisterkaarten xmlns="ccbr.rechtspraak.nl/v1">'
            . '<voorvoegsel>' . $this->xml(trim($voorvoegsel)) . '</voorvoegsel>'
            . '<achternaam>' . $this->xml($achternaam) . '</achternaam>'
            . '<geboorte xmlns:b="' . self::BERICHTEN_NS . '" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">'
            . $geboorte
            . '</geboorte>'
            . '</ZoekRegisterkaarten>';

        $xpath = $this->roep('ZoekRegisterkaarten', $body);

        $treffers = [];
        foreach ($xpath->query('//b:ZoekRegisterkaart') as $kaart) {
            $treffers[] = [
                'aanduiding' => $this->tekst($xpath, 'b:Registerkaartidentificatie/b:RegisterkaartAanduiding', $kaart),
                'kaartnummer' => $this->tekst($xpath, 'b:Registerkaartidentificatie/b:RegisterkaartIndentificatieCode', $kaart),
                'volledige_match' => $this->tekst($xpath, 'b:HonderdProcentMatch', $kaart) === 'true',
                'achternaam' => $this->tekst($xpath, 'b:Geregistreerde/b:SamengesteldeNaam/b:Geslachtsnaam', $kaart),
                'voornamen' => $this->tekst($xpath, 'b:Geregistreerde/b:SamengesteldeNaam/b:Voornamen', $kaart),
                'voorvoegsel' => $this->tekst($xpath, 'b:Geregistreerde/b:SamengesteldeNaam/b:Voorvoegsel', $kaart),
                'geboortedatum' => $this->datum($this->tekst($xpath, 'b:Geregistreerde/b:Geboorte/b:Datum', $kaart)),
                'geboortejaar' => $this->tekst($xpath, 'b:Geregistreerde/b:Geboorte/b:Jaar', $kaart),
                'geboorteplaats' => $this->tekst($xpath, 'b:Geregistreerde/b:Geboorte/b:Woonplaats/b:Naam', $kaart),
            ];
        }

        return [
            'treffers' => $treffers,
            'verificatiecode' => $this->tekst($xpath, '//b:VerificatieCode') ?: null,
        ];
    }

    /**
     * Haalt één registerkaart op met de aanduiding uit een zoekresultaat.
     *
     * @return array<string, mixed>
     */
    public function raadpleeg(string $aanduiding): array
    {
        if (trim($aanduiding) === '') {
            throw new \DomainException(__('Er is geen registerkaart gekozen.'));
        }

        $body = '<RaadpleegRegisterkaart xmlns="ccbr.rechtspraak.nl/v1">'
            . '<registerkaartAanduiding>' . $this->xml($aanduiding) . '</registerkaartAanduiding>'
            . '</RaadpleegRegisterkaart>';

        $xpath = $this->roep('RaadpleegRegisterkaart', $body);

        $grond = $this->tekst($xpath, '//b:Registerkaart/b:Grond');
        $kaartnummer = $this->tekst($xpath, '//b:RegisterkaartIndentificatieCode');

        /*
         * De bewindvoerder of curator kan een mens zijn of een organisatie, en
         * er kunnen er meer dan één zijn (het schema zegt maxOccurs
         * "unbounded"). Een organisatie heeft volgens het schema precies twee
         * velden: Adressen en NaamOrganisatieVolledig. Niet `Naam` — dat
         * element bestaat alleen binnen een adres, als woonplaats of gemeente,
         * en zou hier dus de verkeerde waarde opleveren.
         */
        $vertegenwoordigers = [];
        foreach ($xpath->query('//b:BewindvoerderNietNatuurlijkPersoon | //b:CuratorNietNatuurlijkPersoon') as $knoop) {
            $naam = $this->tekst($xpath, 'b:NaamOrganisatieVolledig', $knoop);
            if ($naam !== '') {
                $vertegenwoordigers[] = $naam;
            }
        }
        foreach ($xpath->query('//b:BewindvoerderNatuurlijkPersoon | //b:CuratorNatuurlijkPersoon') as $knoop) {
            $naam = trim(implode(' ', array_filter([
                $this->tekst($xpath, 'b:SamengesteldeNaam/b:Voornamen', $knoop),
                $this->tekst($xpath, 'b:SamengesteldeNaam/b:Voorvoegsel', $knoop),
                $this->tekst($xpath, 'b:SamengesteldeNaam/b:Geslachtsnaam', $knoop),
            ])));
            if ($naam !== '') {
                $vertegenwoordigers[] = $naam;
            }
        }

        return [
            'kaartnummer' => $kaartnummer,
            'maatregel' => $this->maatregel($grond, $kaartnummer),
            'grond' => $grond,
            'grond_tekst' => self::GRONDEN[$grond][1] ?? null,
            'ingangsdatum' => $this->datum($this->tekst($xpath, '//b:Geldigheid/b:DatumBegin')),
            // Leeg zolang de maatregel loopt. Staat hij er wél, dan begint de
            // bewaartermijn van zes maanden uit de gebruiksvoorwaarden te lopen.
            'einddatum' => $this->datum($this->tekst($xpath, '//b:Geldigheid/b:DatumEinde')),
            'datum_beslissing' => $this->datum($this->tekst($xpath, '//b:DatumBeslissing')),
            'datum_publicatie' => $this->datum($this->tekst($xpath, '//b:DatumPublicatie')),
            'rechtbank' => $this->tekst($xpath, '//b:InstantieBeheer'),
            'beperkt_bewind' => $this->tekst($xpath, '//b:BeperktBewind') === 'true',
            'vertegenwoordigers' => array_values(array_unique($vertegenwoordigers)),
            'achternaam' => $this->tekst($xpath, '//b:Geregistreerde/b:SamengesteldeNaam/b:Geslachtsnaam'),
            'voornamen' => $this->tekst($xpath, '//b:Geregistreerde/b:SamengesteldeNaam/b:Voornamen'),
            'geboortedatum' => $this->datum($this->tekst($xpath, '//b:Geregistreerde/b:Geboorte/b:Datum')),
            'verificatiecode' => $this->tekst($xpath, '//b:VerificatieCode') ?: null,
        ];
    }

    /**
     * Bewind of curatele?
     *
     * De grond noemt de maatregel, het kaartnummer begint met 'BM' of 'CB'.
     * Spreken die twee elkaar tegen, of is de grond onbekend, dan geeft dit
     * null terug — dan staat er op het scherm wat het register letterlijk
     * zegt, in plaats van een label dat er misschien naast zit.
     */
    private function maatregel(string $grond, string $kaartnummer): ?string
    {
        $uitGrond = self::GRONDEN[$grond][0] ?? null;
        $uitNummer = match (strtoupper(substr($kaartnummer, 0, 2))) {
            'BM' => 'bewind',
            'CB' => 'curatele',
            default => null,
        };

        if ($uitGrond && $uitNummer && $uitGrond !== $uitNummer) {
            Log::warning('CCBR: grond en kaartnummer spreken elkaar tegen', [
                'grond' => $grond,
                'kaartnummer' => $kaartnummer,
            ]);

            return null;
        }

        return $uitGrond ?? $uitNummer;
    }

    // ------------------------------------------------------------- de techniek

    /** Doet één aanroep op de dataservice en geeft een XPath over het antwoord. */
    private function roep(string $bewerking, string $body): \DOMXPath
    {
        if (! $this->enabled()) {
            throw new \DomainException(__('De koppeling met het curatele- en bewindregister is niet ingesteld.'));
        }

        $url = (string) config('services.ccbr.url');
        $actie = 'ccbr.rechtspraak.nl/v1/CcbrDataservice/' . $bewerking;

        $envelope = $this->envelope($actie, $url, $this->token(), $body);
        [$status, $antwoord] = $this->post($url, $envelope, $actie, (string) config('services.ccbr.cacert'));

        if ($status !== 200) {
            // Een verlopen of geweigerd token: één keer opnieuw, met een verse.
            if (in_array($status, [401, 403, 500], true)) {
                Cache::forget('ccbr:token');
                $envelope = $this->envelope($actie, $url, $this->token(), $body);
                [$status, $antwoord] = $this->post($url, $envelope, $actie, (string) config('services.ccbr.cacert'));
            }
        }

        if ($status !== 200) {
            Log::warning('CCBR: aanroep mislukt', [
                'bewerking' => $bewerking,
                'status' => $status,
                'fout' => $this->soapFout($antwoord),
            ]);

            throw new \DomainException(__('Het curatele- en bewindregister reageert niet. Probeer het zo opnieuw.'));
        }

        $xpath = $this->xpath($antwoord);

        // Het register meldt inhoudelijke fouten in een Foutmelding, niet met
        // een HTTP-status. Dat is bijvoorbeeld een onacceptabele zoekvraag.
        $melding = $this->tekst($xpath, '//b:Foutmelding');
        if ($melding !== '') {
            Log::info('CCBR: foutmelding uit het register', ['bewerking' => $bewerking, 'melding' => $melding]);

            throw new \DomainException($melding);
        }

        return $xpath;
    }

    /**
     * Het SAML-token, een uur geldig bij de Rechtspraak. Hier vijf minuten
     * korter bewaard, zodat een aanroep nooit op de grens valt.
     */
    private function token(): string
    {
        return Cache::remember('ccbr:token', now()->addMinutes(55), function () {
            $sts = (string) config('services.ccbr.sts');
            $actie = 'http://docs.oasis-open.org/ws-sx/ws-trust/200512/RST/Issue';

            $envelope = '<s:Envelope ' . $this->namespaces() . '>'
                . '<s:Header>'
                . '<a:Action s:mustUnderstand="1">' . $actie . '</a:Action>'
                . '<a:MessageID>urn:uuid:' . Str::uuid() . '</a:MessageID>'
                . '<a:ReplyTo><a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address></a:ReplyTo>'
                . '<a:To s:mustUnderstand="1">' . $sts . '</a:To>'
                . '<o:Security s:mustUnderstand="1" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">'
                . $this->tijdstempel()
                . '<o:UsernameToken u:Id="uuid-' . Str::uuid() . '-1">'
                . '<o:Username>' . $this->xml((string) config('services.ccbr.username')) . '</o:Username>'
                . '<o:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'
                . $this->xml((string) config('services.ccbr.password'))
                . '</o:Password>'
                . '</o:UsernameToken></o:Security></s:Header>'
                . '<s:Body><trust:RequestSecurityToken xmlns:trust="http://docs.oasis-open.org/ws-sx/ws-trust/200512">'
                . '<wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">'
                . '<wsa:EndpointReference xmlns:wsa="http://www.w3.org/2005/08/addressing">'
                . '<wsa:Address>' . $this->xml((string) config('services.ccbr.realm')) . '</wsa:Address>'
                . '</wsa:EndpointReference></wsp:AppliesTo>'
                . '<trust:KeyType>http://docs.oasis-open.org/ws-sx/ws-trust/200512/Bearer</trust:KeyType>'
                . '<trust:RequestType>http://docs.oasis-open.org/ws-sx/ws-trust/200512/Issue</trust:RequestType>'
                . '</trust:RequestSecurityToken></s:Body></s:Envelope>';

            // De STS draait op een gewoon, publiek vertrouwd certificaat.
            [$status, $antwoord] = $this->post($sts, $envelope, $actie, null);

            if ($status !== 200) {
                Log::warning('CCBR: token ophalen mislukt', [
                    'status' => $status,
                    'fout' => $this->soapFout($antwoord),
                ]);

                throw new \DomainException(__('Inloggen bij het curatele- en bewindregister lukt niet. Controleer de gebruikersnaam en het wachtwoord.'));
            }

            /*
             * De assertie letterlijk uit het antwoord knippen. Niet via DOM:
             * de assertie is ondertekend en opnieuw serialiseren breekt de
             * signatuur. De prefix (`saml:`) wordt meegenomen zoals hij is.
             */
            if (! preg_match('#<([a-z0-9]+):Assertion[\s>].*?</\1:Assertion>#si', $antwoord, $m)) {
                Log::warning('CCBR: geen assertie in het antwoord van de token-service');

                throw new \DomainException(__('Het curatele- en bewindregister gaf geen geldig token terug.'));
            }

            return $m[0];
        });
    }

    /**
     * Eén SOAP-aanroep.
     *
     * Dit gaat door de Http-facade en niet door curl rechtstreeks, zodat de
     * antwoorden in tests te vervangen zijn (`Http::fake()`). De officiële
     * voorbeeldberichten van de Rechtspraak liggen in tests/fixtures/ccbr en
     * dienen daar als testmateriaal — daarmee is de ontleding te toetsen
     * zonder ooit de kaart van een echt mens op te vragen.
     *
     * `verify` krijgt het pad naar de PKIoverheid-stam. Dat is iets anders dan
     * verificatie uitzetten: er wordt nog steeds gecontroleerd, alleen tegen
     * die stam in plaats van tegen de publieke truststore.
     *
     * @return array{0: int, 1: string} status en antwoord
     */
    private function post(string $url, string $body, string $actie, ?string $cacert): array
    {
        $verzoek = Http::timeout(30)
            ->connectTimeout(10)
            ->withBody($body, 'application/soap+xml; charset=utf-8; action="' . $actie . '"');

        if ($cacert && is_file($cacert)) {
            $verzoek = $verzoek->withOptions(['verify' => $cacert]);
        }

        try {
            $antwoord = $verzoek->post($url);
        } catch (\Throwable $e) {
            Log::warning('CCBR: verbinding mislukt', ['url' => $url, 'fout' => $e->getMessage()]);

            throw new \DomainException(__('Het curatele- en bewindregister is niet bereikbaar.'));
        }

        return [$antwoord->status(), $antwoord->body()];
    }

    private function envelope(string $actie, string $url, string $assertie, string $body): string
    {
        return '<s:Envelope ' . $this->namespaces() . '>'
            . '<s:Header>'
            . '<a:Action s:mustUnderstand="1">' . $actie . '</a:Action>'
            . '<a:MessageID>urn:uuid:' . Str::uuid() . '</a:MessageID>'
            . '<a:ReplyTo><a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address></a:ReplyTo>'
            . '<a:To s:mustUnderstand="1">' . $url . '</a:To>'
            . '<o:Security s:mustUnderstand="1" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">'
            . $this->tijdstempel() . $assertie
            . '</o:Security></s:Header>'
            . '<s:Body>' . $body . '</s:Body></s:Envelope>';
    }

    private function namespaces(): string
    {
        return 'xmlns:s="http://www.w3.org/2003/05/soap-envelope" '
            . 'xmlns:a="http://www.w3.org/2005/08/addressing" '
            . 'xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"';
    }

    private function tijdstempel(): string
    {
        return '<u:Timestamp u:Id="_0">'
            . '<u:Created>' . gmdate('Y-m-d\TH:i:s.000\Z') . '</u:Created>'
            . '<u:Expires>' . gmdate('Y-m-d\TH:i:s.000\Z', time() + 300) . '</u:Expires>'
            . '</u:Timestamp>';
    }

    /**
     * XPath over het antwoord.
     *
     * De namespace van het register (`ccbr.rechtspraak.nl/v1`) heeft geen
     * schema ervoor en is daarmee geen geldige URI. libxml waarschuwt daarover
     * bij élk antwoord; die waarschuwingen worden hier opgevangen in plaats
     * van naar het logboek geschreven. Op de XPath heeft het geen invloed.
     */
    private function xpath(string $antwoord): \DOMXPath
    {
        $eerder = libxml_use_internal_errors(true);

        try {
            $dom = new \DOMDocument();
            $gelukt = $dom->loadXML($antwoord);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($eerder);
        }

        if (! $gelukt) {
            Log::warning('CCBR: antwoord is geen geldige XML', ['begin' => mb_substr($antwoord, 0, 200)]);

            throw new \DomainException(__('Het curatele- en bewindregister gaf een onleesbaar antwoord.'));
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('b', self::BERICHTEN_NS);

        return $xpath;
    }

    private function tekst(\DOMXPath $xpath, string $pad, ?\DOMNode $context = null): string
    {
        $knopen = $context ? $xpath->query($pad, $context) : $xpath->query($pad);

        if (! $knopen || $knopen->length === 0) {
            return '';
        }

        $knoop = $knopen->item(0);

        // Een leeg element met i:nil="true" is "niet gevuld", niet "leeg".
        if ($knoop instanceof \DOMElement && $knoop->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'nil') === 'true') {
            return '';
        }

        return trim((string) $knoop->textContent);
    }

    /** "1991-04-01T00:00:00" → "1991-04-01"; leeg blijft null. */
    private function datum(string $waarde): ?string
    {
        return $waarde === '' ? null : substr($waarde, 0, 10);
    }

    private function xml(string $waarde): string
    {
        return htmlspecialchars($waarde, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** De leesbare tekst uit een SOAP-fout, voor het logboek. */
    private function soapFout(string $antwoord): string
    {
        if (preg_match('#<(?:[a-z0-9]+:)?Text[^>]*>(.*?)</(?:[a-z0-9]+:)?Text>#si', $antwoord, $m)) {
            return trim(strip_tags($m[1]));
        }

        return mb_substr(trim(strip_tags($antwoord)), 0, 200);
    }
}
