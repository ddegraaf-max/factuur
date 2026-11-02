<?php

namespace Tests\Feature;

use App\Http\Controllers\HealthController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** De statuspagina van Fachora toont de onderdelen van de marktplaats, niet de factuuronderdelen van EasyInvoice (1.97.1). */
class FachoraStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_fachora_lists_its_own_components_in_both_languages(): void
    {
        config(['brand.active' => 'fachora', 'fachora.ceidg_token' => 'jwt', 'services.anthropic.key' => 'sk-ant-test', 'services.stripe.secret' => 'sk_test_x']);
        Cache::forever(HealthController::HEARTBEAT_KEY, now()->timestamp);

        $pl = (string) $this->get('/status')->assertOk()->getContent();
        foreach (['Rejestr przedsiębiorców (CEIDG)', 'Kody SMS', 'Asystent AI przy opisie zlecenia', 'Zgłoszenia i e-maile potwierdzające', 'Płatności dla firm (Stripe)', 'Zadania w tle'] as $label) {
            $this->assertStringContainsString($label, $pl);
        }
        foreach (['Faktury i generowanie PDF', 'Przypomnienia i windykacja', 'Logowanie i 2FA'] as $label) {
            $this->assertStringNotContainsString($label, $pl, 'factuuronderdelen horen niet op de Fachora-statuspagina');
        }

        $en = (string) $this->get('/status?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('Business register (CEIDG)', $en);
        $this->assertStringContainsString('Background tasks', $en);
        $this->assertStringNotContainsString('PDF generation', $en);
    }

    public function test_without_a_stripe_key_payments_show_as_not_set_up_and_the_banner_stays_green(): void
    {
        config(['brand.active' => 'fachora', 'fachora.ceidg_token' => 'jwt', 'services.anthropic.key' => 'sk-ant-test', 'services.stripe.secret' => null]);
        Cache::forever(HealthController::HEARTBEAT_KEY, now()->timestamp);

        $html = (string) $this->get('/status?lang=en')->assertOk()->getContent();
        $this->assertStringContainsString('Not set up yet', $html);
        $this->assertStringContainsString('All systems operational', $html, 'een bewust nog niet ingerichte betaalkoppeling is geen storing');
        $this->assertStringNotContainsString('Outage', $html);
    }

    public function test_easyinvoice_keeps_its_invoice_components(): void
    {
        config(['brand.active' => 'easyinvoice']);
        $html = (string) $this->get('/status')->assertOk()->getContent();
        $this->assertStringContainsString('PDF-generatie', $html); // de ampersand staat als &amp; in de HTML
        $this->assertStringNotContainsString('CEIDG', $html);
    }
}
