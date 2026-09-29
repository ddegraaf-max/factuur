<?php

namespace Tests\Feature;

use App\Mail\PaymentDemandMail;
use App\Mail\VerificationCodeMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentDemand;
use App\Models\User;
use App\Services\FreeDemandImport;
use App\Services\PaymentDemandService;
use App\Support\LegalInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Gratis aanmaning maken (/aanmaning-maken): de brief als PDF zonder account,
 * waarbij niets wordt opgeslagen of verstuurd. Wie de aanmaning online wil
 * versturen, neemt hem mee naar een account en bevestigt eerst zijn e-mailadres.
 */
class FreeDemandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Http::fake();
        $this->travelTo(Carbon::parse('2026-10-05 10:00'));
    }

    /** @return array<string, mixed> */
    private function form(array $override = []): array
    {
        return $override + [
            'van_bedrijf' => 'Jansen Timmerwerk',
            'van_email' => 'jan@jansentimmerwerk.test',
            'van_adres' => "Dorpsstraat 1\n1431 AB Aalsmeer",
            'van_iban' => 'NL91 ABNA 0417 1643 00',
            'van_kvk' => '12345678',
            'geen_btw_aftrek' => '0',
            'aan_naam' => 'Bakkerij Het Stoepje',
            'aan_email' => 'jan@hetstoepje.test',
            'aan_adres' => "Kerkstraat 3\n1211 CK Hilversum",
            'klant' => 'zakelijk',
            'factuurnummer' => '2026-017',
            'factuurdatum' => '2026-08-01',
            'vervaldatum' => '2026-08-31',
            'bedrag' => '1.250,50',
            'btw' => '21',
            'rente' => '1',
        ];
    }

    public function test_anyone_downloads_the_letter_and_nothing_is_stored_or_sent(): void
    {
        $this->get('/aanmaning-maken')->assertOk()
            ->assertSee('Gratis aanmaning maken')
            ->assertSee('Download aanmaning (PDF)');

        $response = $this->post(route('aanmaning-maken.download'), $this->form());
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('aanmaning-2026-017.pdf', (string) $response->headers->get('Content-Disposition'));

        $this->assertSame(0, PaymentDemand::count());
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Customer::count());
        Mail::assertNothingSent();
    }

    public function test_the_letter_follows_the_law_for_business_and_consumer(): void
    {
        $service = app(PaymentDemandService::class);

        // Zakelijk: handelsrente, zelf gekozen termijn, geen post-dagen.
        $business = $service->draft(['bedrag' => 1250.50, 'termijn' => 10] + $this->form());
        $this->assertFalse($business->exists);
        $this->assertSame('business', $business->debtor_type);
        $this->assertSame('2026-10-15', $business->deadline->toDateString());
        $claim = $service->claimAsSent($business);
        $this->assertEqualsWithDelta(LegalInterest::interest(1250.50, Carbon::parse('2026-08-31'), now(), true)['total'], $claim['interest'], 0.001);
        $this->assertEqualsWithDelta(LegalInterest::collectionCosts(1250.50), $claim['costs_total'], 0.001);

        // Particulier: minstens veertien dagen, vanaf de dag na ontvangst, met twee dagen voor de post.
        $consumer = $service->draft(['bedrag' => 400, 'klant' => 'particulier', 'geen_btw_aftrek' => '1', 'rente' => '0'] + $this->form());
        $this->assertSame('consumer', $consumer->debtor_type);
        $this->assertSame(14, $consumer->term_days);
        $this->assertSame('2026-10-22', $consumer->deadline->toDateString());
        $this->assertEqualsWithDelta(12.6, (float) $consumer->costs_vat, 0.001);
        $plain = $service->claimAsSent($consumer);
        $this->assertSame(0.0, $plain['interest']);

        $text = \App\Support\DemandText::letter($consumer, $plain);
        $this->assertStringContainsString('binnen 14 dagen nadat u deze aanmaning heeft ontvangen', $text['term']);
        $this->assertStringContainsString('7 oktober 2026', $text['term']);
        $this->assertStringContainsString('22 oktober 2026', $text['term']);
        $this->assertStringContainsString('Neem dan contact met ons op', $text['respond']);
        $this->assertStringContainsString(money(72.6), $text['consequence']);

        $this->post(route('aanmaning-maken.download'), $this->form(['klant' => 'particulier', 'termijn' => '7']))->assertSessionHasErrors('termijn');
        $this->post(route('aanmaning-maken.download'), $this->form(['vervaldatum' => '2026-10-20']))->assertSessionHasErrors('vervaldatum');
        $this->post(route('aanmaning-maken.download'), $this->form(['bedrag' => '0']))->assertSessionHasErrors('bedrag');
    }

    public function test_the_calculation_needs_only_amounts_and_dates(): void
    {
        $this->getJson(route('aanmaning-maken.calculation', ['bedrag' => '1.000,00', 'vervaldatum' => '2026-08-31', 'klant' => 'zakelijk', 'rente' => 1]))
            ->assertOk()
            ->assertJson([
                'with_interest' => true,
                'principal' => money(1000),
                'costs' => money(150),
                'term_days' => 7,
            ])
            ->assertJsonPath('note', fn ($note) => str_contains($note, '12 oktober 2026'));

        $this->getJson(route('aanmaning-maken.calculation', ['bedrag' => '1000', 'vervaldatum' => '2026-08-31', 'klant' => 'particulier', 'rente' => 0, 'geen_btw_aftrek' => 1]))
            ->assertOk()
            ->assertJson(['with_interest' => false, 'total' => money(1000), 'costs' => money(181.5), 'term_days' => 14]);

        $this->getJson(route('aanmaning-maken.calculation', ['bedrag' => 'veel', 'vervaldatum' => '2026-08-31', 'klant' => 'zakelijk']))->assertStatus(422);
    }

    public function test_the_demand_comes_along_to_a_new_account_and_goes_out_after_the_address_is_confirmed(): void
    {
        $this->post(route('aanmaning-maken.keep'), $this->form(['termijn' => '10', 'rente' => '0']))->assertRedirect(route('register'));
        $this->get(route('register'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('prefillKind', 'demand')
            ->where('prefill.companyName', 'Jansen Timmerwerk')
            ->where('prefill.email', 'jan@jansentimmerwerk.test')
            ->where('prefill.customer', 'Bakkerij Het Stoepje'));

        $this->post(route('register'), [
            'name' => 'Jan Jansen', 'companyName' => 'Jansen Timmerwerk', 'email' => 'jan@jansentimmerwerk.test',
            'password' => 'geheim-wachtwoord-1', 'acceptTerms' => true,
        ])->assertRedirect(route('verification.show'));

        $user = User::where('email', 'jan@jansentimmerwerk.test')->firstOrFail();
        $company = $user->company;
        $this->assertSame('NL91ABNA0417164300', $company->iban);
        $this->assertSame('1431 AB', $company->postal_code);

        $invoice = Invoice::withoutGlobalScope('company')->where('company_id', $company->id)->firstOrFail();
        $this->assertSame('2026-017', $invoice->number);
        $this->assertSame('overdue', $invoice->status);
        $this->assertEqualsWithDelta(1250.50, (float) $invoice->total, 0.001);
        $this->assertEqualsWithDelta(217.03, (float) $invoice->vat_total, 0.001);
        $this->assertSame('jan@hetstoepje.test', $invoice->customer_email);
        $this->assertSame('business', $invoice->customer->type);
        $this->assertSame(1, $invoice->lines()->count());

        // Tot het adres is bevestigd, is er niets naar de klant gegaan.
        Mail::assertSent(VerificationCodeMail::class);
        Mail::assertNotSent(PaymentDemandMail::class);
        $this->assertSame(0, PaymentDemand::withoutGlobalScope('company')->count());

        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->post(route('verification.verify'), ['code' => $code])
            ->assertRedirect(route('invoices.show', ['invoice' => $invoice->id, 'aanmaning' => 1, 'termijn' => 10, 'rente' => 0]));

        $this->get(route('invoices.show', $invoice))->assertOk()->assertInertia(fn ($page) => $page
            ->where('invoice.demand.blocker', null)
            ->where('invoice.demand.current', null));

        // Eén klik in het venster: de aanmaning gaat uit, met de keuzes uit de tool.
        $this->post(route('demands.store', $invoice), ['term_days' => 10, 'with_interest' => false])->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();
        $this->assertSame('business', $demand->debtor_type);
        $this->assertSame(10, $demand->term_days);
        $this->assertFalse($demand->with_interest);
        Mail::assertSent(PaymentDemandMail::class, fn (PaymentDemandMail $mail) => $mail->hasTo('jan@hetstoepje.test'));
    }

    public function test_a_kept_demand_without_customer_address_explains_what_is_missing(): void
    {
        $company = \App\Models\Company::create(['name' => 'Jansen Timmerwerk', 'email' => 'jan@jansentimmerwerk.test', 'country' => 'NL', 'trial_ends_at' => now()->addDays(14)]);
        $invoice = app(FreeDemandImport::class)->apply($company, $this->form(['aan_email' => null, 'bedrag' => 300, 'klant' => 'particulier', 'geen_btw_aftrek' => '1']));

        $this->assertNotNull($invoice);
        $this->assertTrue((bool) $company->fresh()->kor);
        $this->assertTrue((bool) $invoice->vat_exempt);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->vat_total, 0.001);
        $this->assertSame('consumer', $invoice->customer->type);
        $this->assertStringContainsString('e-mailadres', (string) app(PaymentDemandService::class)->blocker($invoice->fresh()));
    }
}
