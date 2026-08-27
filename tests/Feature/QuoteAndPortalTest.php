<?php

namespace Tests\Feature;

use App\Mail\PortalCodeMail;
use App\Mail\QuoteAcceptedMail;
use App\Mail\QuoteDecisionMail;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

class QuoteAndPortalTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** 1×1 transparante PNG — een geldige handtekening voor het tekenveld. */
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /** Een verstuurde offerte met een uniek klantadres zonder facturen (portaalflows zijn gast-routes: geen actingAs). */
    private function sentQuoteForFreshEmail(int $companyId): array
    {
        $quote = Quote::withoutGlobalScope('company')->where('company_id', $companyId)->where('status', 'sent')->firstOrFail();
        $email = 'alleen-offerte-' . uniqid() . '@example.com';
        $quote->forceFill(['customer_email' => $email])->save();
        $quote->ensurePortalToken();

        return [$quote->fresh(), $email];
    }

    public function test_portal_sends_a_code_to_a_customer_who_only_has_a_quote(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        [$quote, $email] = $this->sentQuoteForFreshEmail($user->company_id);

        // Offertelink → eerst verificatie.
        $this->get(route('portal.quote', $quote->portal_token))->assertRedirect(route('portal.verify.show'));

        // Bug uit 1.37: alleen facturen telden mee, dus geen code voor offerteklanten.
        $this->withSession(['portal_pending_email' => $email])
            ->post(route('portal.code.send'))
            ->assertRedirect();

        Mail::assertSent(PortalCodeMail::class, fn ($mail) => $mail->hasTo($email));
    }

    public function test_customer_can_sign_a_quote_and_both_parties_get_a_mail(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        [$quote, $email] = $this->sentQuoteForFreshEmail($user->company_id);

        $verified = ['portal_email' => $email, 'portal_verified_at' => now()->timestamp];

        $this->withSession($verified)->get(route('portal.quote', $quote->portal_token))->assertOk();

        $this->withSession($verified)->post(route('portal.quote.sign', $quote->portal_token), [
            'signed_name' => 'Test Klant',
            'signature' => self::PNG,
            'agree' => '1',
        ])->assertRedirect();

        $quote->refresh();
        $this->assertSame('accepted', $quote->status);
        $this->assertNotNull($quote->signed_at);
        $this->assertSame('Test Klant', $quote->signed_name);
        $this->assertNotNull($quote->accept_mail_sent_at);

        Mail::assertSent(QuoteDecisionMail::class);
        Mail::assertSent(QuoteAcceptedMail::class, fn ($mail) => $mail->hasTo($email));
    }

    public function test_owner_can_mark_quote_accepted_with_confirmation_mail(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $quote = Quote::where('status', 'sent')->whereNotNull('customer_email')->firstOrFail();

        $this->post(route('quotes.accept', $quote), ['send_confirmation' => true])->assertRedirect();

        $quote->refresh();
        $this->assertSame('accepted', $quote->status);
        $this->assertNotNull($quote->accept_mail_sent_at);
        Mail::assertSent(QuoteAcceptedMail::class, fn ($mail) => $mail->hasTo($quote->customer_email));
    }

    public function test_portal_overview_lists_invoices_and_quotes(): void
    {
        $user = $this->demoUser();
        [$quote, $email] = $this->sentQuoteForFreshEmail($user->company_id);

        $this->withSession(['portal_email' => $email, 'portal_verified_at' => now()->timestamp])
            ->get(route('portal.index'))
            ->assertOk()
            ->assertSee($quote->number);
    }
}
