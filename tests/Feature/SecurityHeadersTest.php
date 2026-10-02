<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * De beveiligingskoppen, en het vertrouwen in de proxy van het platform.
 *
 * Er stond geen enkele kop op de antwoorden van de productieomgeving, en
 * Laravel dacht dat elk verzoek onversleuteld binnenkwam omdat
 * X-Forwarded-Proto niet werd vertrouwd. Dat laatste was te zien aan een
 * registratie die op de validatie strandde: die kreeg een 302 naar
 * http://easybookkeeper.nl/register.
 *
 * Deze toetsen staan er zodat dat niet ongemerkt terugkomt. Ze raken de
 * database niet en hebben dus geen RefreshDatabase nodig.
 */
class SecurityHeadersTest extends TestCase
{
    public function test_de_koppen_staan_op_een_gewone_pagina(): void
    {
        $antwoord = $this->get('/register');

        // SAMEORIGIN, geen DENY: de factuurpagina en de inkoopfactuur laden hun
        // PDF in een iframe van de eigen site, en DENY blokkeert ook die.
        $antwoord->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $antwoord->assertHeader('X-Content-Type-Options', 'nosniff');
        $antwoord->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString(
            'camera=()',
            (string) $antwoord->headers->get('Permissions-Policy'),
        );
    }

    /**
     * Juist op de antwoorden die géén pagina zijn: daar gaat een browser zelf
     * raden wat hij binnenkrijgt, en dat is precies wat nosniff tegenhoudt.
     */
    public function test_de_koppen_staan_ook_op_een_fout(): void
    {
        $antwoord = $this->get('/deze-pagina-bestaat-niet');

        $antwoord->assertNotFound();
        $antwoord->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * HSTS hoort alleen op een versleutelde verbinding. Stuur je hem over http
     * mee, dan negeert elke browser hem — en dan staat er een kop die een
     * zekerheid suggereert die er niet is.
     */
    public function test_hsts_alleen_over_https(): void
    {
        $overHttp = $this->get('http://localhost/register');
        $this->assertNull($overHttp->headers->get('Strict-Transport-Security'));

        $overHttps = $this->get('https://localhost/register');
        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $overHttps->headers->get('Strict-Transport-Security'),
        );
    }

    /**
     * De kern van de proxy-instelling: met X-Forwarded-Proto: https moet
     * Laravel het verzoek als versleuteld zien. Zonder trustProxies niet, en
     * dan bouwt elke redirect-terug een http-adres — wat precies het gedrag
     * was dat op easybookkeeper.nl te zien viel.
     *
     * Hier wordt bewust niet op url() getoetst. Die leest APP_URL, en dat is in
     * de testomgeving http://localhost; zo'n toets zou falen om een reden die
     * niets met de proxy te maken heeft.
     */
    public function test_de_proxykop_maakt_het_verzoek_versleuteld(): void
    {
        $this->get('/register', ['X-Forwarded-Proto' => 'https']);

        $this->assertTrue(
            request()->isSecure(),
            'X-Forwarded-Proto wordt niet vertrouwd — zet trustProxies in bootstrap/app.php.',
        );
    }
}
