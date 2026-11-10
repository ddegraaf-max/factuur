<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private ?string $costFile = null;

    /**
     * De kostenbewaker telt bij een storing door in een bestand en in het
     * werkgeheugen; elke toets begint met een eigen, leeg bestand en geheugen.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->costFile = sys_get_temp_dir() . '/kostenplafond-' . bin2hex(random_bytes(6)) . '.json';
        config(['kostenplafond.bestand' => $this->costFile]);
        \App\Services\CostGuard::flushMemory();
    }

    protected function tearDown(): void
    {
        if ($this->costFile !== null) {
            @unlink($this->costFile);
        }
        parent::tearDown();
    }

    /**
     * Alle URL's uit de sitemap-index en haar deelsitemaps.
     *
     * @return list<string>
     */
    protected function sitemapUrls(): array
    {
        $index = (string) $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $index, $m);
        $this->assertNotEmpty($m[1], 'Sitemap-index zonder deelsitemaps');

        $urls = [];
        foreach ($m[1] as $sitemap) {
            $xml = (string) $this->get((string) parse_url($sitemap, PHP_URL_PATH))->assertOk()->getContent();
            preg_match_all('#<loc>([^<]+)</loc>#', $xml, $u);
            $urls = array_merge($urls, $u[1]);
        }

        return $urls;
    }
}
