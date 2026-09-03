<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
