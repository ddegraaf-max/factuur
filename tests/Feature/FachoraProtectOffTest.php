<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fachora Protect staat standaard uit (config fachora.protect = false, 1.91): de hele
 * voorkant zwijgt erover, de pagina /fachora-protect bestaat niet, en Mollie komt
 * nergens meer voor (alleen Stripe). Met de vlag aan komt alles terug.
 */
class FachoraProtectOffTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/', '/jak-to-dziala', '/faq', '/dla-firm', '/standard', '/o-nas', '/kontakt', '/regulamin', '/polityka-prywatnosci', '/demo'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null]);
    }

    public function test_protect_is_off_by_default_and_absent_from_the_public_site(): void
    {
        config(['services.stripe.secret' => null]);
        $this->assertFalse(config('fachora.protect'));
        $this->assertFalse(fachora_protect());

        $this->get('/fachora-protect')->assertNotFound();

        foreach (['pl', 'en'] as $lang) {
            foreach (self::PAGES as $path) {
                $html = (string) $this->get($path . '?lang=' . $lang)->assertOk()->getContent();
                $pos = strpos($html, 'Fachora Protect');
                $ctx = $pos === false ? '' : ' … ' . substr($html, max(0, $pos - 160), 320) . ' …';
                $this->assertStringNotContainsString('Fachora Protect', $html, "{$path} ({$lang}) noemt Fachora Protect nog:{$ctx}");
                $this->assertStringNotContainsString('/fachora-protect', $html, "{$path} ({$lang}) linkt nog naar /fachora-protect");
                $this->assertStringNotContainsStringIgnoringCase('Mollie', $html, "{$path} ({$lang}) noemt Mollie nog");
            }
        }
    }

    public function test_the_mollie_webhook_is_gone(): void
    {
        $this->post('/webhooks/mollie-fachora', ['id' => 'tr_x'])->assertNotFound();
    }

    public function test_with_the_flag_on_protect_returns_everywhere(): void
    {
        config(['fachora.protect' => true, 'services.stripe.secret' => 'sk_test_x']);
        $this->assertTrue(fachora_protect());

        $this->get('/fachora-protect')->assertOk();
        foreach (['pl', 'en'] as $lang) {
            $html = (string) $this->get('/?lang=' . $lang)->assertOk()->getContent();
            $this->assertStringContainsString('Fachora Protect', $html);
            $this->assertStringContainsString('/fachora-protect', $html);
            $this->assertStringNotContainsStringIgnoringCase('Mollie', $html);
        }
        $this->assertStringContainsString('Stripe', (string) $this->get('/fachora-protect')->getContent());
    }
}
