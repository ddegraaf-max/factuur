<?php

namespace Tests\Feature;

use App\Services\CcbrService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * De koppeling met het Centraal Curatele- en Bewindregister.
 *
 * ── Waarmee hier getoetst wordt ───────────────────────────────────────────
 *
 * Met de voorbeeldberichten uit het officiële documentatiepakket van de
 * Rechtspraak (tests/fixtures/ccbr). Die bevatten fictieve gegevens — "Jan
 * Jansen", geboren in "Lutjebroek" — en dat is bewust: de ontleding is zo te
 * toetsen zonder ooit de registerkaart van een echt mens op te vragen. Een
 * mens opzoeken om te kijken of je parser werkt, is precies de verwerking voor
 * een ander doel die de gebruiksvoorwaarden verbieden.
 *
 * Waar het misgaat bij dit soort koppelingen:
 *
 * - de ondertekende SAML-assertie die onderweg wordt gewijzigd, waardoor de
 *   signatuur niet meer klopt;
 * - een label dat op een ongedocumenteerde code wordt gebaseerd, zodat er
 *   "bewind" op het scherm staat waar het curatele is;
 * - een foutmelding uit het register die als leeg resultaat wordt gelezen,
 *   waardoor "niets gevonden" iets heel anders betekent dan het lijkt.
 */
class CcbrTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ccbr' => [
            'username' => 'TESTGEBRUIKER',
            'password' => 'testwachtwoord',
            'sts' => 'https://sts.rechtspraak.nl/adfs/services/trust/13/usernamemixed',
            'url' => 'https://ccbrservice.rechtspraak.nl/ccbrdataservice.svc',
            'realm' => 'https://ccbrservice.rechtspraak.nl/',
            'cacert' => resource_path('certs/pkioverheid-private-root-g1.pem'),
        ]]);

        Cache::forget('ccbr:token');
    }

    private function bericht(string $naam): string
    {
        return file_get_contents(base_path("tests/fixtures/ccbr/{$naam}.xml"));
    }

    /** @var array{0: string, 1: int} het antwoord dat de dataservice nu geeft */
    private array $antwoordVanDeDataservice = ['', 200];

    /**
     * STS antwoordt met een token, de dataservice met het meegegeven bericht.
     *
     * ── Waarom dit één stub is en geen lijst met URL-patronen ─────────────
     *
     * `Http::fake()` een tweede keer aanroepen vóegt stubs samen in plaats van
     * ze te vervangen, en de eerste die matcht wint. Een test die twee keer een
     * ander antwoord nodig heeft, krijgt dan stil het eerste — en dan lijken er
     * velden te ontbreken die gewoon in een ander bericht zitten. Daar ben ik
     * bij het bouwen van deze koppeling een half uur aan kwijt geweest. Eén
     * stub die routeert, met een variabele erachter, kan die val niet zetten.
     */
    private function doeAlsof(string $antwoord, int $status = 200): void
    {
        $this->antwoordVanDeDataservice = [$antwoord, $status];

        Http::fake(function ($verzoek) {
            if (str_contains($verzoek->url(), 'sts.rechtspraak.nl')) {
                return Http::response($this->bericht('token-antwoord'), 200);
            }

            return Http::response(...$this->antwoordVanDeDataservice);
        });
    }

    // ------------------------------------------------------------------ zoeken

    public function test_het_zoekantwoord_van_de_rechtspraak_wordt_goed_ontleed(): void
    {
        $this->doeAlsof($this->bericht('zoek-antwoord'));

        $uit = app(CcbrService::class)->zoek('Jansen', '1991-04-01');

        $this->assertCount(1, $uit['treffers']);
        $this->assertNotNull($uit['verificatiecode']);

        $kaart = $uit['treffers'][0];
        $this->assertSame('BM00001/0001', $kaart['kaartnummer']);
        $this->assertSame('Jansen', $kaart['achternaam']);
        $this->assertSame('Jan', $kaart['voornamen']);
        $this->assertSame('1991-04-01', $kaart['geboortedatum']);
        $this->assertSame('Lutjebroek', $kaart['geboorteplaats']);
        $this->assertTrue($kaart['volledige_match']);
        $this->assertNotEmpty($kaart['aanduiding'], 'zonder aanduiding kan de kaart niet opgevraagd worden');
    }

    public function test_zoeken_op_alleen_een_geboortejaar_mag(): void
    {
        $this->doeAlsof($this->bericht('zoek-antwoord'));

        app(CcbrService::class)->zoek('Jansen', null, 1991);

        Http::assertSent(function ($verzoek) {
            if (! str_contains($verzoek->url(), 'ccbrservice')) {
                return false;
            }

            // Het jaar gevuld, de datum expliciet leeg — zo wil het register het.
            return str_contains($verzoek->body(), '<b:Jaar>1991</b:Jaar>')
                && str_contains($verzoek->body(), '<b:Datum i:nil="true"/>');
        });
    }

    public function test_zonder_geboortedatum_of_jaar_wordt_er_niet_gezocht(): void
    {
        Http::fake();

        $this->expectException(\DomainException::class);
        app(CcbrService::class)->zoek('Jansen');
    }

    public function test_zonder_inloggegevens_bestaat_de_koppeling_niet(): void
    {
        config(['services.ccbr.username' => null, 'services.ccbr.password' => null]);
        Http::fake();

        $dienst = app(CcbrService::class);
        $this->assertFalse($dienst->enabled());

        $this->expectException(\DomainException::class);
        $dienst->zoek('Jansen', '1991-04-01');
    }

    // --------------------------------------------------------- de registerkaart

    public function test_de_registerkaart_wordt_goed_ontleed_en_als_bewind_herkend(): void
    {
        $this->doeAlsof($this->bericht('raadpleeg-antwoord'));

        $kaart = app(CcbrService::class)->raadpleeg('willekeurige-aanduiding');

        $this->assertSame('BM00001/0001', $kaart['kaartnummer']);
        $this->assertSame('02', $kaart['grond']);

        // De grond noemt de maatregel; het kaartnummer begint met BM. Die twee
        // moeten hetzelfde zeggen, anders klopt het label niet.
        $this->assertSame('bewind', $kaart['maatregel']);
        $this->assertSame('Bewind: lichamelijke of geestelijke toestand', $kaart['grond_tekst']);

        $this->assertSame('Rechtbank Noord-Holland, zittingsplaats Lutjebroek', $kaart['rechtbank']);
        $this->assertSame('Jansen', $kaart['achternaam']);
    }

    public function test_een_onbekende_grond_levert_geen_verzonnen_label_op(): void
    {
        // Grond 99 bestaat niet. Dan hoort er geen maatregel uit te komen op
        // basis van de grond — alleen nog wat het kaartnummer zegt.
        $antwoord = str_replace('<b:Grond>02</b:Grond>', '<b:Grond>99</b:Grond>', $this->bericht('raadpleeg-antwoord'));
        $this->doeAlsof($antwoord);

        $kaart = app(CcbrService::class)->raadpleeg('x');

        $this->assertSame('99', $kaart['grond']);
        $this->assertNull($kaart['grond_tekst'], 'een onbekende code hoort geen tekst te krijgen');
        $this->assertSame('bewind', $kaart['maatregel'], 'het kaartnummer BM blijft dan het enige signaal');
    }

    public function test_als_grond_en_kaartnummer_elkaar_tegenspreken_wordt_er_niets_beweerd(): void
    {
        /*
         * Grond 03 is curatele, maar het kaartnummer begint met BM (bewind).
         * Zoiets hoort niet te kunnen. Gebeurt het toch, dan is het beter geen
         * label te tonen dan het verkeerde: iemand gaat hierop handelen.
         */
        $antwoord = str_replace('<b:Grond>02</b:Grond>', '<b:Grond>03</b:Grond>', $this->bericht('raadpleeg-antwoord'));
        $this->doeAlsof($antwoord);

        $kaart = app(CcbrService::class)->raadpleeg('x');

        $this->assertNull($kaart['maatregel']);
        $this->assertSame('Curatele: geestelijke stoornis', $kaart['grond_tekst']);
    }

    // -------------------------------------------------------------- het token

    public function test_de_ondertekende_assertie_gaat_letterlijk_mee(): void
    {
        /*
         * De assertie is door de Rechtspraak ondertekend. Wordt hij onderweg
         * opnieuw opgebouwd of geherformatteerd, dan klopt de signatuur niet
         * meer en weigert de dataservice het bericht. Daarom moet de blok tekst
         * er teken voor teken in terugkomen.
         */
        $this->doeAlsof($this->bericht('zoek-antwoord'));

        app(CcbrService::class)->zoek('Jansen', '1991-04-01');

        $token = $this->bericht('token-antwoord');
        preg_match('#<(saml):Assertion[\s>].*?</saml:Assertion>#si', $token, $m);
        $this->assertNotEmpty($m, 'het testbericht hoort een assertie te bevatten');

        Http::assertSent(function ($verzoek) use ($m) {
            return str_contains($verzoek->url(), 'ccbrservice')
                && str_contains($verzoek->body(), $m[0]);
        });
    }

    public function test_het_token_wordt_hergebruikt_en_niet_bij_elke_vraag_opgehaald(): void
    {
        $this->doeAlsof($this->bericht('zoek-antwoord'));

        $dienst = app(CcbrService::class);
        $dienst->zoek('Jansen', '1991-04-01');
        $dienst->zoek('Pietersen', '1970-01-01');

        $naarSts = 0;
        Http::assertSent(function ($verzoek) use (&$naarSts) {
            if (str_contains($verzoek->url(), 'sts.rechtspraak.nl')) {
                $naarSts++;
            }

            return true;
        });

        $this->assertSame(1, $naarSts, 'het token is een uur geldig en hoort één keer opgehaald te worden');
    }

    // ------------------------------------------------------------ foutgevallen

    public function test_een_foutmelding_uit_het_register_is_geen_leeg_resultaat(): void
    {
        /*
         * Het register meldt een onacceptabele vraag in een Foutmelding, met
         * HTTP 200. Lees je dat als "geen treffers", dan concludeer je dat
         * iemand niet onder bewind staat terwijl je het niet weet.
         */
        $antwoord = str_replace(
            '<b:Foutmelding i:nil="true"/>',
            '<b:Foutmelding>De geboortedatum is niet acceptabel.</b:Foutmelding>',
            $this->bericht('zoek-antwoord')
        );
        $this->doeAlsof($antwoord);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('De geboortedatum is niet acceptabel.');

        app(CcbrService::class)->zoek('Jansen', '1991-04-01');
    }

    public function test_een_onbereikbaar_register_geeft_een_leesbare_melding(): void
    {
        Http::fake([
            'sts.rechtspraak.nl/*' => Http::response($this->bericht('token-antwoord'), 200),
            'ccbrservice.rechtspraak.nl/*' => Http::response('<html>Service Unavailable</html>', 503),
        ]);

        $this->expectException(\DomainException::class);

        app(CcbrService::class)->zoek('Jansen', '1991-04-01');
    }

    public function test_de_pkioverheid_stam_staat_in_de_repo_en_is_de_juiste(): void
    {
        /*
         * Zonder deze stam mislukt elke verbinding met de dataservice: de
         * certificaatketen eindigt bij "Staat der Nederlanden Private Root CA -
         * G1", die in geen enkele truststore zit. De vingerafdruk is die van
         * cert.pkioverheid.nl/PrivateRootCA-G1.cer.
         */
        $pad = resource_path('certs/pkioverheid-private-root-g1.pem');
        $this->assertFileExists($pad);

        $cert = openssl_x509_read(file_get_contents($pad));
        $this->assertNotFalse($cert, 'het bestand hoort een leesbaar certificaat te zijn');

        $this->assertSame(
            '0257ce27b52408e24ee2c0945640b723c5bc66ddbda4ada58c60357604f0e675',
            openssl_x509_fingerprint($cert, 'sha256')
        );

        $gegevens = openssl_x509_parse($cert);
        $this->assertSame('Staat der Nederlanden Private Root CA - G1', $gegevens['subject']['CN']);
    }
}
