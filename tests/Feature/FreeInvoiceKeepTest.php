<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\FreeInvoiceImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Een factuur uit de gratis tool meenemen naar een nieuw account: alleen op
 * eigen verzoek, en dan staan bedrijfsgegevens, klant en factuur er meteen in.
 */
class FreeInvoiceKeepTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(array $extra = []): array
    {
        return $extra + [
            'van_bedrijf' => 'De Boer Timmerwerken',
            'van_email' => 'sanne@example.com',
            'van_adres' => "Dorpsstraat 12\n3441 ab Woerden",
            'van_kvk' => '1234 5678',
            'van_btw' => 'nl 0012.34.567.b01',
            'van_iban' => 'NL91 ABNA 0417 1643 00',
            'aan_bedrijf' => 'Bouwbedrijf Jansen B.V.',
            'aan_adres' => "Havenweg 3\n2401 CD Alphen aan den Rijn",
            'factuurnummer' => '2026-014',
            'factuurdatum' => '2026-09-20',
            'vervaldatum' => '2026-10-20',
            'btw_type' => 'normaal',
            'opmerking' => 'Bedankt voor de opdracht.',
            'regels' => [
                ['omschrijving' => 'Dakkapel plaatsen', 'aantal' => '1', 'prijs' => '1.250,50', 'btw' => '21'],
                ['omschrijving' => 'Afvoer puin', 'aantal' => '2', 'prijs' => '40', 'btw' => '9'],
            ],
        ];
    }

    private function register(): User
    {
        $this->post('/register', [
            'name' => 'Sanne de Boer', 'companyName' => 'De Boer Timmerwerken', 'email' => 'sanne@example.com',
            'password' => 'geheim-wachtwoord-1', 'acceptTerms' => true,
        ])->assertSessionHasNoErrors()->assertRedirect(route('verification.show'));

        return User::where('email', 'sanne@example.com')->firstOrFail();
    }

    public function test_downloading_leaves_nothing_behind(): void
    {
        $this->post(route('gratis-factuur.download'), $this->invoice())->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSessionMissing(FreeInvoiceImport::SESSION);

        $this->get('/gratis-factuur-maken')->assertOk()->assertSee(route('gratis-factuur.keep'), false);
        $this->get('/register')->assertOk()->assertInertia(fn ($page) => $page->where('prefill', null));
    }

    public function test_the_invoice_comes_along_to_a_new_account(): void
    {
        Mail::fake();
        Http::fake();

        $this->post(route('gratis-factuur.keep'), $this->invoice())->assertRedirect(route('register'));

        $this->get('/register')->assertOk()->assertInertia(fn ($page) => $page
            ->where('prefill.companyName', 'De Boer Timmerwerken')
            ->where('prefill.email', 'sanne@example.com')
            ->where('prefill.customer', 'Bouwbedrijf Jansen B.V.'));

        $user = $this->register();
        $company = $user->company;

        $this->assertSame('Dorpsstraat 12', $company->address_line);
        $this->assertSame('3441 AB', $company->postal_code);
        $this->assertSame('Woerden', $company->city);
        $this->assertSame('12345678', $company->kvk_number);
        $this->assertSame('NL001234567B01', $company->vat_number);
        $this->assertSame('NL91ABNA0417164300', $company->iban);

        $customer = Customer::withoutGlobalScopes()->where('company_id', $company->id)->sole();
        $this->assertSame('Bouwbedrijf Jansen B.V.', $customer->name);
        $this->assertSame('Alphen aan den Rijn', $customer->city);

        $draft = Invoice::withoutGlobalScopes()->where('company_id', $company->id)->with('lines')->sole();
        $this->assertSame('draft', $draft->status);
        $this->assertSame('2026-014', $draft->reference);
        $this->assertSame(30, (int) $draft->payment_terms);
        $this->assertCount(2, $draft->lines);
        // 1.250,50 + 21% en 80,00 + 9%
        $this->assertEquals(1600.31, (float) $draft->total);
        $this->assertSame('Bedankt voor de opdracht.', $draft->notes);

        // Na het bevestigen van het e-mailadres begint het account bij die factuur.
        $this->post(route('verification.verify'), ['code' => $user->fresh()->verification_code])
            ->assertRedirect(route('invoices.edit', $draft));
        $this->get(route('invoices.edit', $draft))->assertOk();

        // De gegevens zijn uit de sessie: een tweede account krijgt ze niet.
        $this->assertNull(session(FreeInvoiceImport::SESSION));
    }

    public function test_an_invoice_without_vat_keeps_its_reason(): void
    {
        Mail::fake();
        Http::fake();

        $this->post(route('gratis-factuur.keep'), $this->invoice(['btw_type' => 'verlegd', 'opmerking' => null]))->assertRedirect(route('register'));
        $user = $this->register();

        $draft = Invoice::withoutGlobalScopes()->where('company_id', $user->company_id)->with('lines')->sole();
        $this->assertEquals(1330.50, (float) $draft->total);
        $this->assertEquals(0.0, (float) $draft->vat_total);
        $this->assertStringContainsString('Btw verlegd', (string) $draft->notes);
    }

    public function test_registering_without_an_invoice_still_works(): void
    {
        Mail::fake();
        Http::fake();

        $user = $this->register();

        $this->assertSame(0, Invoice::withoutGlobalScopes()->where('company_id', $user->company_id)->count());
        $this->assertNull($user->company->address_line);
    }
}
