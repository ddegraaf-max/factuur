<?php

namespace Tests\Feature;

use App\Services\NipService;
use App\Services\WindykacjaService;
use App\Support\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Marktlaag (config/markets.php): Nederland en Polen op dezelfde code.
 * Het merk lopra_pl draait in markt 'pl' met Poolse taal, PLN en btw 23/8/5/0.
 */
class MarketTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_dutch_market_is_the_default(): void
    {
        $this->assertSame('nl', Market::key());
        $this->assertSame('EUR', Market::currency());
        $this->assertSame([21, 9, 0], Market::vatRates());
        $this->assertSame("€\u{00A0}1.234,50", Market::money(1234.5));
        $this->assertSame("-€\u{00A0}12,00", money(-12));
        $this->assertSame('Armaere Gerechtsdeurwaarders', Market::incasso('partner_name'));
        $this->assertSame('KvK-nummer', market('registry.label'));
    }

    public function test_polish_brand_switches_market_language_currency_and_vat(): void
    {
        config(['brand.active' => 'lopra_pl']);

        $this->assertSame('pl', Market::key());
        $this->assertTrue(Market::isPl());
        $this->assertSame('PLN', Market::currency());
        $this->assertSame([23, 8, 5, 0], Market::vatRates());
        $this->assertSame(23, Market::defaultVatRate());
        $this->assertSame(8, Market::nearestVatRate(9));
        $this->assertSame("1\u{00A0}234,50\u{00A0}zł", Market::money(1234.5));
        $this->assertSame('NIP', market('tax_id.label'));
        $this->assertSame('Creditline Polska', Market::incasso('partner_name'));
        $this->assertSame('BLIK / Przelewy24', Market::forClient()['online_payment_label']);
        $this->assertSame('Lopra', brand('name'));
        $this->assertSame('lopra.pl', brand('domain'));
    }

    public function test_polish_homepage_and_pages_render_and_dutch_pages_are_hidden(): void
    {
        config(['brand.active' => 'lopra_pl', 'app.url' => 'https://lopra.pl']);

        $home = $this->get('/')->assertOk();
        $home->assertSee('lang="pl"', false);
        $home->assertSee('Cała Twoja firma');
        $home->assertSee('Creditline');
        $this->assertStringNotContainsStringIgnoringCase('easyinvoice', preg_replace('#<script\b[^>]*>.*?</script>#si', '', (string) $home->getContent()));

        foreach (['/faq', '/kontakt', '/o-nas', '/regulamin', '/polityka-prywatnosci', '/kalkulator-odsetek', '/przenies-sie-z/fakturownia'] as $path) {
            $this->get($path)->assertOk();
        }

        // Nederlandse marketingpagina's bestaan niet in de Poolse markt.
        foreach (['/veelgestelde-vragen', '/kennisbank', '/gratis-factuur-maken', '/overstappen-van/wefact', '/voorwaarden'] as $path) {
            $this->get($path)->assertNotFound();
        }

        $sitemap = (string) $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('/kalkulator-odsetek', $sitemap);
        $this->assertStringNotContainsString('/kennisbank', $sitemap);

        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('lang', 'pl');
        $this->get('/login')->assertOk()->assertSee('data-locale="pl"', false)->assertSee('&quot;market&quot;:{&quot;key&quot;:&quot;pl&quot;', false);
    }

    public function test_polish_pages_do_not_exist_in_the_dutch_market(): void
    {
        foreach (['/kalkulator-odsetek', '/regulamin', '/przenies-sie-z/fakturownia'] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->get('/veelgestelde-vragen')->assertOk();
    }

    public function test_nip_validation_and_windykacja_calculations(): void
    {
        $this->assertTrue(NipService::valid('526-025-08-83'));   // geldig controlecijfer
        $this->assertTrue(NipService::valid('5260250883'));
        $this->assertFalse(NipService::valid('5260250884'));
        $this->assertFalse(NipService::valid('123'));
        $this->assertSame(['ul. Prosta 51', '00-838', 'Warszawa'], NipService::splitAddress('ul. Prosta 51, 00-838 Warszawa'));

        config(['brand.active' => 'lopra_pl']);
        $w = app(WindykacjaService::class);
        $this->assertSame(0.14, $w->interestRate());
        $this->assertSame(383.56, $w->interest(10000, 100));          // 10 000 × 14% × 100/365
        $this->assertSame(['eur' => 40, 'pln' => 172.0], $w->compensation(4000));
        $this->assertSame(['eur' => 70, 'pln' => 301.0], $w->compensation(12400));
        $this->assertSame(['eur' => 100, 'pln' => 430.0], $w->compensation(80000));
    }

    public function test_polish_registration_requires_a_valid_nip_and_sets_polish_defaults(): void
    {
        config(['brand.active' => 'lopra_pl']);

        $payload = [
            'firstName' => 'Anna', 'lastName' => 'Kowalska', 'email' => 'anna@example.pl',
            'password' => 'haslo-Bardzo-Tajne1', 'password_confirmation' => 'haslo-Bardzo-Tajne1',
            'companyName' => 'Studio Wnętrz Kowalska', 'companyType' => 'jdg',
            'kvkNumber' => '', 'vatNumber' => '5260250884', 'acceptTerms' => '1',
        ];
        $this->post('/register', $payload)->assertSessionHasErrors('vatNumber');

        $this->post('/register', ['vatNumber' => '526-025-08-83'] + $payload)->assertSessionHasNoErrors();

        $company = \App\Models\Company::withoutGlobalScopes()->where('name', 'Studio Wnętrz Kowalska')->first();
        $this->assertNotNull($company);
        $this->assertSame('PL', $company->country);
        $this->assertSame('PLN', $company->currency);
        $this->assertSame('5260250883', $company->vat_number);
        $this->assertSame('FV/{year}/{sequence:4}', $company->invoice_number_format);
        $this->assertSame(14, (int) $company->default_payment_terms);
    }

    public function test_wezwanie_pdf_and_sale_request_for_a_polish_company(): void
    {
        config(['brand.active' => 'lopra_pl']);
        \Illuminate\Support\Facades\Mail::fake();

        $user = $this->demoUser();
        $invoice = \App\Models\Invoice::withoutGlobalScopes()->where('company_id', $user->company_id)
            ->whereNotIn('status', ['draft', 'paid', 'cancelled'])->where('is_credit', false)->first();
        $this->assertNotNull($invoice, 'Demo-administratie zonder openstaande factuur');

        $this->actingAs($user)->get(route('windykacja.claim', $invoice))->assertOk()->assertJsonStructure(['principal', 'days', 'interest', 'compensation', 'total', 'deadline']);

        $pdf = $this->actingAs($user)->get(route('windykacja.wezwanie', $invoice))->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $pdf->getContent());

        $this->actingAs($user)->post(route('windykacja.wykup', $invoice), ['note' => 'Proszę o ofertę'])->assertSessionHas('flash');
        $this->assertNotNull($invoice->fresh()->sale_requested_at);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\WykupRequestMail::class);
    }

    public function test_windykacja_routes_are_hidden_in_the_dutch_market(): void
    {
        $user = $this->demoUser();
        $invoice = \App\Models\Invoice::withoutGlobalScopes()->where('company_id', $user->company_id)->first();

        $this->actingAs($user)->get(route('windykacja.wezwanie', $invoice))->assertNotFound();
    }
}
