<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Mail\PaymentReminderMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ReminderService;
use App\Services\UblGenerator;
use App\Services\WindykacjaService;
use App\Support\DocumentLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Een betaling die je boekt nadat de factuur is verstuurd, hoort overal door te
 * werken waar de klant een bedrag te zien krijgt: de factuurmail, de
 * herinnering, de e-factuur én de PDF die bij elk van die mails zit.
 *
 * Tot 1.76.8 trok de PDF alleen aanbetalingen af. De herinnering zei dan
 * "nog € 710,00 te betalen" en de bijgevoegde factuur "Te betalen € 1.210,00".
 */
class PaymentOnDocumentsTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** Een verstuurde demo-factuur zonder betalingen, met regels. */
    private function openInvoice(): Invoice
    {
        $this->actingAs($this->demoUser());

        return Invoice::regular()
            ->whereIn('status', ['sent', 'overdue'])
            ->has('lines')
            ->doesntHave('payments')
            ->where('total', '>', 100)
            ->orderBy('id')
            ->firstOrFail()
            ->load('lines');
    }

    /** Boekt een post op de factuur; het opslaan herrekent paid_total en de status. */
    private function book(Invoice $invoice, float $amount, string $kind = 'payment', ?string $reference = null): Payment
    {
        $payment = Payment::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'kind' => $kind,
            'amount' => $amount,
            'paid_on' => now()->toDateString(),
            'method' => $kind === 'payment' ? 'bank_transfer' : 'other',
            'reference' => $reference,
        ]);

        $invoice->refresh();

        return $payment;
    }

    private function pdf(Invoice $invoice, string $template): string
    {
        return DocumentLocale::using('nl', fn () => view("pdf.invoice-{$template}", [
            'invoice' => $invoice,
            'company' => $invoice->brandedCompany(),
        ])->render());
    }

    public function test_de_pdf_toont_een_geboekte_betaling_en_het_restant(): void
    {
        $invoice = $this->openInvoice();
        $this->book($invoice, 100);
        $rest = round((float) $invoice->total - 100, 2);

        $this->assertSame('partial', $invoice->status);
        $this->assertEqualsWithDelta($rest, $invoice->remaining_amount, 0.001);

        foreach (['modern', 'classic', 'minimal', 'stationery'] as $template) {
            $html = $this->pdf($invoice, $template);

            $this->assertStringContainsString('Totaal incl. btw', $html, $template);
            $this->assertStringContainsString('Reeds ontvangen', $html, $template);
            $this->assertStringContainsString('Te betalen', $html, $template);
            $this->assertStringContainsString(money($rest), $html, $template . ': het restant hoort op de PDF');
        }
    }

    public function test_een_kwijtschelding_en_een_verrekening_krijgen_hun_eigen_opschrift(): void
    {
        $invoice = $this->openInvoice();
        $this->book($invoice, 25, 'write_off');
        $this->book($invoice, 50, 'credit', 'Verrekend met creditnota CN-2026-001');
        $rest = round((float) $invoice->total - 75, 2);

        $html = $this->pdf($invoice, 'modern');

        $this->assertStringContainsString('Kwijtgescholden', $html);
        $this->assertStringContainsString('Verrekend met creditnota CN-2026-001', $html);
        $this->assertStringContainsString(money($rest), $html);
    }

    public function test_zonder_betaling_verandert_de_pdf_niet(): void
    {
        $invoice = $this->openInvoice();

        foreach (['modern', 'classic', 'minimal', 'stationery'] as $template) {
            $html = $this->pdf($invoice, $template);

            $this->assertStringNotContainsString('Totaal incl. btw', $html, $template);
            $this->assertStringNotContainsString('Reeds ontvangen', $html, $template);
            $this->assertStringContainsString(money($invoice->total), $html, $template);
        }
    }

    public function test_de_factuurmail_noemt_het_te_betalen_bedrag(): void
    {
        $invoice = $this->openInvoice();
        $this->book($invoice, 100);

        $body = DocumentLocale::using('nl', fn () => (new InvoiceMail($invoice, 'pdf'))->render());

        $this->assertStringContainsString('het te betalen bedrag is', $body);
        $this->assertStringContainsString(money($invoice->remaining_amount), $body);
    }

    public function test_de_herinnering_noemt_het_openstaande_bedrag(): void
    {
        Mail::fake();
        $invoice = $this->openInvoice();
        $this->book($invoice, 100);

        app(ReminderService::class)->sendManual($invoice);

        Mail::assertSent(PaymentReminderMail::class, function (PaymentReminderMail $mail) use ($invoice) {
            return str_contains($mail->render(), money($invoice->remaining_amount));
        });
    }

    public function test_de_e_factuur_geeft_het_vooruitbetaalde_en_het_restant(): void
    {
        $invoice = $this->openInvoice();
        $this->book($invoice, 100);
        $rest = number_format((float) $invoice->total - 100, 2, '.', '');

        $xml = app(UblGenerator::class)->generate($invoice);

        $this->assertMatchesRegularExpression('/<cbc:PrepaidAmount currencyID="[A-Z]{3}">100\.00<\/cbc:PrepaidAmount>/', $xml);
        $this->assertMatchesRegularExpression('/<cbc:PayableAmount currencyID="[A-Z]{3}">' . preg_quote($rest, '/') . '<\/cbc:PayableAmount>/', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'De e-factuur is geldige XML');
    }

    public function test_zonder_betaling_heeft_de_e_factuur_geen_vooruitbetaling(): void
    {
        $invoice = $this->openInvoice();
        $total = number_format((float) $invoice->total, 2, '.', '');

        $xml = app(UblGenerator::class)->generate($invoice);

        $this->assertStringNotContainsString('PrepaidAmount', $xml);
        $this->assertMatchesRegularExpression('/<cbc:PayableAmount currencyID="[A-Z]{3}">' . preg_quote($total, '/') . '<\/cbc:PayableAmount>/', $xml);
    }

    /**
     * Factuur 2026-0021 van 9 oktober 2026: op het formulier € 5.000 als "reeds
     * ontvangen" ingevuld en meteen verstuurd. De verrekening werkte paid_total
     * bij op een ander exemplaar van de factuur; het versturen zag nog 0. De
     * mail vroeg € 6.050 en de factuur bleef op "verstuurd" staan.
     */
    public function test_direct_versturen_met_reeds_ontvangen_geeft_de_mail_het_restant(): void
    {
        Mail::fake();
        $this->actingAs($this->demoUser());
        $customer = Customer::orderBy('id')->firstOrFail();
        $customer->forceFill(['email' => 'klant@example.com'])->save();

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 14,
            'reference' => 'Alleen de btw nog',
            'lines' => [
                ['description' => 'Montage', 'quantity' => 1, 'unit' => 'stuk', 'unit_price' => 5000, 'vat_rate' => 21, 'discount_pct' => 0],
            ],
            'advances' => [
                ['description' => 'Reeds ontvangen', 'date' => now()->toDateString(), 'amount' => 5000],
            ],
            'action' => 'send',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $invoice = Invoice::where('reference', 'Alleen de btw nog')->firstOrFail();

        $this->assertSame('partial', $invoice->status, 'met € 5.000 van € 6.050 binnen is de factuur deels betaald');
        $this->assertEqualsWithDelta(5000.0, (float) $invoice->paid_total, 0.001);
        $this->assertEqualsWithDelta(1050.0, $invoice->remaining_amount, 0.001);

        Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) {
            $body = DocumentLocale::using('nl', fn () => $mail->render());

            return str_contains($body, 'het te betalen bedrag is')
                && str_contains($body, money(1050));
        });
    }

    public function test_de_poolse_aanmaning_rekent_met_het_openstaande_bedrag(): void
    {
        $invoice = $this->openInvoice();
        $this->book($invoice, 100);
        config(['brand.active' => 'lopra_pl']);

        $claim = app(WindykacjaService::class)->claim($invoice);

        $this->assertEqualsWithDelta(round((float) $invoice->total - 100, 2), $claim['principal'], 0.001);
    }
}
