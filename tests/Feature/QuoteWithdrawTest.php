<?php

namespace Tests\Feature;

use App\Mail\QuoteWithdrawnMail;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\QuoteManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Een verstuurde offerte laten vervallen, bijvoorbeeld omdat er een nieuwe
 * voor in de plaats komt: status "vervallen", de klant krijgt een nette mail
 * met de vervanger, het portaal laat niet meer ondertekenen, en met
 * "Opnieuw aanbieden" is het terug te draaien.
 */
class QuoteWithdrawTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function sentQuote(int $companyId): Quote
    {
        $quote = Quote::withoutGlobalScope('company')->where('company_id', $companyId)->where('status', 'sent')->firstOrFail();
        $quote->forceFill(['customer_email' => 'klant-' . uniqid() . '@example.com', 'language' => 'nl'])->save();
        $quote->ensurePortalToken();

        return $quote->fresh();
    }

    public function test_withdraw_marks_the_quote_and_mails_the_customer_about_the_replacement(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);

        $old = $this->sentQuote($user->company_id);
        // Een nieuwe offerte voor dezelfde klant: de vervanger.
        $new = $old->replicate(['number', 'portal_token', 'converted_invoice_id']);
        $new->forceFill(['status' => 'draft', 'sent_at' => null])->save();
        app(QuoteManager::class)->send($new->fresh());
        $new = $new->fresh();
        $this->assertSame('sent', $new->status);
        $this->assertNotSame($old->number, $new->number);

        // De pagina biedt de nieuwe offerte aan als vervanger.
        $this->get(route('quotes.show', $old))->assertOk()
            ->assertInertia(fn ($page) => $page->where('quote.replacement_options.0.id', $new->id)->where('quote.replacement_options.0.newer', true));

        Mail::fake();
        $this->post(route('quotes.withdraw', $old), [
            'replaced_by_quote_id' => $new->id,
            'note' => 'De aanvraag van de gemeente is gewijzigd.',
            'notify' => 1,
        ])->assertRedirect()->assertSessionHas('flash');

        $old->refresh();
        $this->assertSame('withdrawn', $old->status);
        $this->assertNotNull($old->withdrawn_at);
        $this->assertSame($new->id, $old->replaced_by_quote_id);
        $this->assertSame('De aanvraag van de gemeente is gewijzigd.', $old->withdrawal_note);
        $this->assertNotNull($old->withdrawal_mail_sent_at);
        $this->assertSame($old->customer_email, $old->withdrawal_mail_sent_to);

        Mail::assertSent(QuoteWithdrawnMail::class, function (QuoteWithdrawnMail $mail) use ($old, $new) {
            $html = $mail->render();

            return $mail->hasTo($old->customer_email)
                && str_contains($html, $old->number)
                && str_contains($html, $new->number)
                && str_contains($html, 'De aanvraag van de gemeente is gewijzigd.')
                && str_contains($html, $new->portalUrl())
                && empty($mail->attachments) && empty($mail->rawAttachments);
        });

        $this->assertDatabaseHas('activity_logs', ['action' => 'withdrawn', 'company_id' => $user->company_id]);

        // De PDF van de vervallen offerte draagt het stempel, zodat hij nooit voor geldig doorgaat.
        $html = view('pdf.quote', ['quote' => $old->fresh()->load('lines'), 'company' => $old->brandedCompany()])->render();
        $this->assertStringContainsString('VERVALLEN', $html);

        // De lijst kent de nieuwe status; op de pagina staat de vervanger.
        $this->get(route('quotes.index', ['status' => 'withdrawn']))->assertOk()
            ->assertInertia(fn ($page) => $page->where('counts.withdrawn', 1)->where('quotes.data.0.id', $old->id));
        $this->get(route('quotes.show', $old))->assertOk()
            ->assertInertia(fn ($page) => $page->where('quote.status', 'withdrawn')->where('quote.replaced_by.number', $new->number));
    }

    public function test_an_accepted_quote_can_be_withdrawn_and_the_customer_is_asked_to_confirm_the_new_one(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);

        $old = $this->sentQuote($user->company_id);
        $new = $old->replicate(['number', 'portal_token', 'converted_invoice_id']);
        $new->forceFill(['status' => 'draft', 'sent_at' => null])->save();
        app(QuoteManager::class)->send($new->fresh());

        // De klant had de oude al digitaal ondertekend.
        app(QuoteManager::class)->accept($old);
        $old->forceFill(['signed_name' => 'Lin Zheng', 'signed_at' => now(), 'signed_email' => $old->customer_email])->save();
        $old = $old->fresh();
        $this->get(route('quotes.show', $old))->assertOk()
            ->assertInertia(fn ($page) => $page->where('quote.status', 'accepted')->where('quote.replacement_options.0.id', $new->fresh()->id));

        $this->post(route('quotes.withdraw', $old), ['replaced_by_quote_id' => $new->id, 'notify' => 1])->assertRedirect()->assertSessionHas('flash');
        $old->refresh();
        $this->assertSame('withdrawn', $old->status);
        $this->assertNotNull($old->accepted_at, 'De acceptatie blijft als historie bewaard');
        $this->assertSame('Lin Zheng', $old->signed_name);

        Mail::assertSent(QuoteWithdrawnMail::class, function (QuoteWithdrawnMail $mail) use ($old) {
            $html = $mail->render();

            return $mail->hasTo($old->customer_email)
                && str_contains($html, 'heeft geaccepteerd')
                && str_contains($html, 'opnieuw te bevestigen');
        });
    }

    public function test_portal_shows_the_withdrawal_and_no_longer_lets_the_customer_sign(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $quote = $this->sentQuote($user->company_id);
        app(QuoteManager::class)->withdraw($quote, null, 'Per abuis verstuurd.');

        $verified = ['portal_email' => $quote->customer_email, 'portal_verified_at' => now()->timestamp];
        $this->withSession($verified)->get(route('portal.quote', $quote->portal_token))->assertOk()
            ->assertInertia(fn ($page) => $page->where('quote.status', 'withdrawn')->where('quote.withdrawal_note', 'Per abuis verstuurd.')->where('quote.replacement', null));

        $this->withSession($verified)->post(route('portal.quote.sign', $quote->portal_token), [
            'signed_name' => 'Test Klant',
            'signature' => 'data:image/png;base64,iVBORw0KGgo=',
            'agree' => '1',
        ])->assertRedirect()->assertSessionHas('error');
        $this->assertSame('withdrawn', $quote->fresh()->status);
        $this->assertNull($quote->fresh()->signed_at);
    }

    public function test_only_sent_or_expired_quotes_can_be_withdrawn_and_reopen_undoes_it(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);

        // Al gefactureerd? Dan niet meer.
        $invoiced = Quote::withoutGlobalScope('company')->where('company_id', $user->company_id)->where('status', 'accepted')->firstOrFail();
        $invoiced->forceFill(['converted_invoice_id' => Invoice::withoutGlobalScope('company')->where('company_id', $user->company_id)->value('id')])->save();
        $this->post(route('quotes.withdraw', $invoiced))->assertRedirect()->assertSessionHasErrors('quote');
        $this->assertSame('accepted', $invoiced->fresh()->status);

        $expired = $this->sentQuote($user->company_id);
        $draft = $expired->replicate(['number', 'portal_token', 'converted_invoice_id']);
        $draft->forceFill(['status' => 'draft', 'sent_at' => null])->save();
        $this->post(route('quotes.withdraw', $draft))->assertRedirect()->assertSessionHasErrors('quote');
        $this->assertSame('draft', $draft->fresh()->status);

        // Verlopen mag wel, maar een vervanger uit een andere administratie niet.
        $expired->forceFill(['status' => 'expired', 'valid_until' => now()->subDay()])->save();
        $other = $this->demoUser();
        $this->actingAs($user);
        $foreign = Quote::withoutGlobalScope('company')->where('company_id', $other->company_id)->where('status', 'sent')->firstOrFail();
        $this->post(route('quotes.withdraw', $expired), ['replaced_by_quote_id' => $foreign->id])->assertRedirect()->assertSessionHasErrors('quote');
        $this->assertSame('expired', $expired->fresh()->status);

        // Zonder vervanger en zonder mail.
        $this->post(route('quotes.withdraw', $expired), ['notify' => 0])->assertRedirect()->assertSessionHas('flash');
        $expired->refresh();
        $this->assertSame('withdrawn', $expired->status);
        $this->assertNull($expired->replaced_by_quote_id);
        $this->assertNull($expired->withdrawal_mail_sent_at);
        Mail::assertNotSent(QuoteWithdrawnMail::class);

        // Alsnog de klant informeren; daarna wist "opnieuw aanbieden" alles.
        $this->post(route('quotes.withdraw.notify', $expired))->assertRedirect()->assertSessionHas('flash');
        Mail::assertSent(QuoteWithdrawnMail::class, fn (QuoteWithdrawnMail $mail) => $mail->hasTo($expired->customer_email));
        $this->assertNotNull($expired->fresh()->withdrawal_mail_sent_at);

        $this->post(route('quotes.reopen', $expired))->assertRedirect()->assertSessionHas('flash');
        $expired->refresh();
        $this->assertSame('draft', $expired->status);
        $this->assertNull($expired->withdrawn_at);
        $this->assertNull($expired->withdrawal_note);
        $this->assertNull($expired->withdrawal_mail_sent_at);
    }
}
