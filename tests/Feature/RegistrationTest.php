<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Support\Onboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Aanmelden met het korte formulier (naam, bedrijfsnaam, e-mailadres,
 * wachtwoord) en de startlijst die daarna naar de eerste factuur leidt.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $extra = []): array
    {
        return $extra + [
            'name' => 'Sanne de Boer',
            'companyName' => 'De Boer Timmerwerken',
            'email' => 'sanne@example.com',
            'password' => 'geheim-wachtwoord-1',
            'acceptTerms' => true,
            'newsletter' => false,
        ];
    }

    public function test_four_fields_are_enough_to_register(): void
    {
        Mail::fake();
        Http::fake();

        $this->post('/register', $this->form())->assertSessionHasNoErrors()->assertRedirect(route('verification.show'));

        $user = User::where('email', 'sanne@example.com')->firstOrFail();
        $this->assertSame('Sanne de Boer', $user->name);
        $this->assertSame('De Boer Timmerwerken', $user->company->name);
        $this->assertNull($user->company->kvk_number);
        $this->assertTrue($user->company->trial_ends_at->isFuture());
    }

    public function test_a_single_name_is_accepted_and_the_required_fields_are_checked(): void
    {
        Mail::fake();
        Http::fake();

        $this->post('/register', $this->form(['name' => '']))->assertSessionHasErrors('firstName');
        $this->post('/register', $this->form(['companyName' => '']))->assertSessionHasErrors('companyName');
        $this->post('/register', $this->form(['password' => 'kort']))->assertSessionHasErrors('password');
        $this->post('/register', $this->form(['acceptTerms' => false]))->assertSessionHasErrors('acceptTerms');
        $this->assertSame(0, Company::withoutGlobalScopes()->count());

        $this->post('/register', $this->form(['name' => 'Sanne']))->assertSessionHasNoErrors();
        $this->assertSame('Sanne', User::where('email', 'sanne@example.com')->firstOrFail()->name);
    }

    public function test_a_kvk_number_is_still_checked_when_given(): void
    {
        Mail::fake();
        Http::fake();

        $this->post('/register', $this->form(['kvkNumber' => '1234567']))->assertSessionHasErrors('kvkNumber');
        $this->post('/register', $this->form(['kvkNumber' => '12345678']))->assertSessionHasNoErrors();
        $this->post('/register', $this->form(['kvkNumber' => '12345678', 'email' => 'ander@example.com']))->assertSessionHasErrors('kvkNumber');
    }

    public function test_a_repeated_password_must_match_when_the_form_sends_one(): void
    {
        Mail::fake();
        Http::fake();

        $this->post('/register', $this->form(['password_confirmation' => 'iets-anders-123']))->assertSessionHasErrors('password');
    }

    public function test_the_start_list_leads_to_the_first_invoice(): void
    {
        Mail::fake();
        Http::fake();

        $this->post('/register', $this->form())->assertSessionHasNoErrors();
        $user = User::where('email', 'sanne@example.com')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('onboarding.done', 0)
            ->where('onboarding.total', 3)
            ->where('onboarding.steps.0.key', 'company')
            ->where('onboarding.steps.0.done', false));

        $user->company->update(['address_line' => 'Dorpsstraat 1', 'postal_code' => '1234 AB', 'city' => 'Woerden', 'iban' => 'NL91ABNA0417164300', 'kvk_number' => '12345678']);
        $customer = Customer::create(['company_id' => $user->company_id, 'name' => 'Klant BV', 'email' => 'klant@example.com', 'country' => 'NL']);

        $this->assertSame(2, Onboarding::for($user->company->fresh())['done']);

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 30,
            'lines' => [['description' => 'Kozijn plaatsen', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 450, 'vat_rate' => 21]],
            'action' => 'send',
        ])->assertSessionHasNoErrors();

        // Bedrijfsgegevens compleet en iets verstuurd: de lijst is weg.
        $this->assertNull(Onboarding::for($user->company->fresh()));
        $this->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page->where('onboarding', null));
    }

    // ------------------------------------------- een weigerende mailserver

    /**
     * Laat het versturen van de verificatiecode mislukken, zoals een mailserver
     * die de inlog weigert.
     */
    private function mailServerWeigert(): void
    {
        $wachtend = \Mockery::mock();
        $wachtend->shouldReceive('send')
            ->andThrow(new \RuntimeException('Failed to authenticate on SMTP server'));

        Mail::shouldReceive('to')->andReturn($wachtend);
    }

    public function test_aanmelden_lukt_ook_als_de_verificatiemail_niet_verstuurd_kan_worden(): void
    {
        /*
         * Dit is wat er op lopra.nl gebeurde op 30-09-2026. `MAIL_PASSWORD` was
         * daar leeg, dus smtp.resend.com weigerde de inlog. De administratie en
         * de gebruiker waren op dat moment al vastgelegd, en daarna gooide
         * `Mail::send()` een uitzondering — midden in de afhandeling, ná de
         * transactie. De bezoeker kreeg een foutpagina terwijl zijn account
         * bestond, en opnieuw aanmelden zei "dit e-mailadres is al in gebruik".
         *
         * Een mislukte mail is hinderlijk. Het mag geen account opleveren dat je
         * niet kunt gebruiken en niet kunt overdoen.
         */
        Http::fake();
        $this->mailServerWeigert();

        $this->post('/register', $this->form())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.show'));

        $user = User::where('email', 'sanne@example.com')->first();
        $this->assertNotNull($user, 'het account hoort gewoon te bestaan');
        $this->assertNotNull($user->verification_code, 'de code hoort klaar te staan om opnieuw te sturen');
        $this->assertNull($user->email_verified_at);

        // En het scherm zegt wat er is, in plaats van te wachten op een mail
        // die nooit komt.
        $this->get(route('verification.show'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('mailFailed', true));
    }

    public function test_inloggen_met_een_onbevestigd_account_loopt_niet_vast_op_de_mail(): void
    {
        Http::fake();
        Mail::fake();

        $this->post('/register', $this->form())->assertSessionHasNoErrors();
        $this->flushSession();

        // Zonder deze reparatie kwam je hierna nooit meer bij je eigen account:
        // het inloggen maakte een nieuwe code aan en klapte op het versturen.
        $this->travel(16)->minutes();
        $this->mailServerWeigert();

        $this->post('/login', ['email' => 'sanne@example.com', 'password' => 'geheim-wachtwoord-1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.show'));
    }

    public function test_opnieuw_sturen_zegt_eerlijk_dat_het_niet_gelukt_is(): void
    {
        Http::fake();
        Mail::fake();

        $this->post('/register', $this->form())->assertSessionHasNoErrors();

        // De wachttijd tussen twee aanvragen overslaan.
        $this->travel(2)->minutes();
        $this->mailServerWeigert();

        // Geen "nieuwe code verstuurd" terwijl er niets verstuurd is.
        $this->post(route('verification.resend'))
            ->assertSessionHasErrors('code')
            ->assertSessionMissing('flash');
    }
}
