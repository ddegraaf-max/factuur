<?php

namespace Tests\Feature;

use App\Mail\QuoteAcceptedMail;
use App\Mail\QuoteDecisionMail;
use App\Mail\QuoteMail;
use App\Mail\QuoteSignerMail;
use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * 1.107: een offerte die door twee personen wordt ondertekend (bijv. twee bestuurders).
 * De tweede ondertekenaar krijgt een eigen link en mail; de offerte is pas geaccepteerd als beide
 * handtekeningen staan; wie tekende krijgt de ander aan de beurt; herinneringen gaan alleen naar wie
 * nog moet tekenen; het bewijs staat per ondertekenaar op de offerte en in de PDF.
 */
class QuoteCoSignerTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private function quoteWithTwoSigners(): Quote
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['email' => 'klant@example.com']);

        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30, 'action' => 'draft', 'reference' => 'Twee handtekeningen',
            'signers_required' => 2, 'co_signer_name' => 'Jan de Vries', 'co_signer_email' => 'jan@example.com',
            'lines' => [['description' => 'Verbouwing', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 6500, 'vat_rate' => 21, 'discount_pct' => 0]],
        ])->assertRedirect();

        return Quote::where('reference', 'Twee handtekeningen')->firstOrFail();
    }

    private function signAs(string $email, string $token, string $name): \Illuminate\Testing\TestResponse
    {
        return $this->withSession(['portal_email' => $email, 'portal_verified_at' => now()->timestamp])
            ->post(route('portal.quote.sign', $token), ['signed_name' => $name, 'signature' => self::SIGNATURE, 'agree' => '1']);
    }

    public function test_a_second_signer_needs_a_name_and_a_different_email_address(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['email' => 'klant@example.com']);
        $base = [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30, 'action' => 'draft',
            'lines' => [['description' => 'Werk', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 100, 'vat_rate' => 21, 'discount_pct' => 0]],
        ];

        $this->post(route('quotes.store'), $base + ['signers_required' => 2])->assertSessionHasErrors(['co_signer_name', 'co_signer_email']);
        $this->post(route('quotes.store'), $base + ['signers_required' => 2, 'co_signer_name' => 'Jan', 'co_signer_email' => 'KLANT@example.com'])
            ->assertSessionHasErrors(['co_signer_email']);
    }

    public function test_both_signers_sign_in_turn_and_only_then_is_the_quote_accepted(): void
    {
        $quote = $this->quoteWithTwoSigners();
        $this->assertSame(2, (int) $quote->signers_required);
        $this->assertTrue($quote->needsCoSigner());

        // Versturen: de klant krijgt de offertemail, de tweede ondertekenaar zijn eigen uitnodiging met eigen link.
        $this->post(route('quotes.send', $quote))->assertRedirect();
        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertNotNull($quote->co_signer_token);
        $this->assertNotSame($quote->portal_token, $quote->co_signer_token);
        $this->assertNotNull($quote->co_signer_invited_at);
        Mail::assertSent(QuoteMail::class, fn ($m) => $m->hasTo('klant@example.com'));
        Mail::assertSent(QuoteSignerMail::class, fn ($m) => $m->hasTo('jan@example.com') && $m->mode === 'invite' && $m->role === 'co');

        // De tweede ondertekenaar opent zijn link zonder verificatie: de code gaat naar zíjn adres.
        $this->get(route('portal.quote', $quote->co_signer_token))->assertRedirect(route('portal.verify.show'));
        $this->assertSame('jan@example.com', session('portal_pending_email'));

        // De lijst en de offertepagina: nog niemand heeft getekend.
        $row = collect($this->get(route('quotes.index'))->viewData('page')['props']['quotes']['data'])->firstWhere('id', $quote->id);
        $this->assertSame(2, $row['signers_required']);
        $this->assertSame(0, $row['signatures_done']);

        // De klant tekent als eerste: nog niet geaccepteerd, 1 van 2; Jan krijgt "nu u", de ondernemer "1 van 2".
        $this->signAs('klant@example.com', $quote->portal_token, 'Ben van Vliet')->assertRedirect()->assertSessionHas('flash');
        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertNotNull($quote->signed_at);
        $this->assertNull($quote->co_signed_at);
        $this->assertSame(1, $quote->signaturesDone());
        Mail::assertSent(QuoteSignerMail::class, fn ($m) => $m->hasTo('jan@example.com') && $m->mode === 'turn');
        Mail::assertSent(QuoteDecisionMail::class, fn ($m) => $m->partialBy === 'Ben van Vliet');
        Mail::assertNotSent(QuoteAcceptedMail::class);

        // De klant kan niet nog eens tekenen; Jan wel, met zijn eigen geverifieerde adres via zijn eigen link.
        $this->signAs('klant@example.com', $quote->portal_token, 'Ben van Vliet')->assertRedirect()->assertSessionHas('error');
        $page = $this->withSession(['portal_email' => 'jan@example.com', 'portal_verified_at' => now()->timestamp])
            ->get(route('portal.quote', $quote->co_signer_token))->assertOk()->viewData('page')['props']['quote'];
        $this->assertSame('co', $page['signing']['my_role']);
        $this->assertFalse($page['signing']['mine_signed']);
        $this->assertSame('Jan de Vries', $page['signing']['my_name']);

        $this->signAs('jan@example.com', $quote->co_signer_token, 'Jan de Vries')->assertRedirect()->assertSessionHas('flash');
        $quote->refresh();
        $this->assertSame('accepted', $quote->status);
        $this->assertSame('Jan de Vries', $quote->co_signed_name);
        $this->assertSame('jan@example.com', $quote->co_signed_email);
        $this->assertTrue($quote->isFullySigned());
        Mail::assertSent(QuoteDecisionMail::class, fn ($m) => $m->partialBy === null && $m->accepted);

        // Offertepagina van de ondernemer: beide handtekeningen in het bewijsdossier.
        $props = $this->get(route('quotes.show', $quote))->assertOk()->viewData('page')['props']['quote'];
        $this->assertCount(2, $props['signers']);
        $this->assertSame('Ben van Vliet', $props['signers'][0]['signed_name']);
        $this->assertSame('Jan de Vries', $props['signers'][1]['signed_name']);
        $this->assertNotNull($props['signers'][1]['signed_at_label']);
    }

    public function test_reminders_go_only_to_the_signer_who_still_has_to_sign(): void
    {
        $quote = $this->quoteWithTwoSigners();
        $this->post(route('quotes.send', $quote))->assertRedirect();
        $quote->refresh();

        // De klant heeft getekend; een herinnering gaat alleen nog naar Jan.
        $this->signAs('klant@example.com', $quote->portal_token, 'Ben van Vliet')->assertRedirect();
        Mail::fake(); // de ondernemer is nog ingelogd (zelfde demo-administratie als de offerte)
        $this->post(route('quotes.remind', $quote))->assertRedirect()->assertSessionHas('flash');
        Mail::assertSent(QuoteSignerMail::class, fn ($m) => $m->hasTo('jan@example.com') && $m->mode === 'reminder');
        Mail::assertNotSent(QuoteMail::class);

        // De uitnodiging opnieuw sturen kan ook met één klik.
        $this->post(route('quotes.cosigner.invite', $quote))->assertRedirect()->assertSessionHas('flash');
        Mail::assertSent(QuoteSignerMail::class, fn ($m) => $m->hasTo('jan@example.com') && $m->mode === 'invite');
    }

    public function test_a_quote_with_one_signer_works_as_before(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->update(['email' => 'klant@example.com']);
        $this->post(route('quotes.store'), [
            'customer_id' => $customer->id, 'quote_date' => now()->toDateString(), 'valid_days' => 30, 'action' => 'draft', 'reference' => 'Eén handtekening',
            'lines' => [['description' => 'Werk', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 100, 'vat_rate' => 21, 'discount_pct' => 0]],
        ])->assertRedirect();
        $quote = Quote::where('reference', 'Eén handtekening')->firstOrFail();
        $this->post(route('quotes.send', $quote))->assertRedirect();
        Mail::assertNotSent(QuoteSignerMail::class);

        $this->signAs('klant@example.com', $quote->refresh()->portal_token, 'Ben van Vliet')->assertRedirect()->assertSessionHas('flash');
        $this->assertSame('accepted', $quote->refresh()->status);
    }
}
