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
}
