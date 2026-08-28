<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DirectDebitBatch;
use App\Models\Invoice;
use App\Services\SepaDirectDebitService;
use App\Support\Iban;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Automatische incasso: machtiging op de klant, batch van open facturen, geldig pain.008-bestand. */
class DirectDebitTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_iban_validation(): void
    {
        $this->assertTrue(Iban::valid('NL91 ABNA 0417 1643 00'));
        $this->assertTrue(Iban::valid('DE89370400440532013000'));
        $this->assertFalse(Iban::valid('NL91ABNA0417164301'));
        $this->assertFalse(Iban::valid('123'));
        $this->assertSame('NL91 ABNA 0417 1643 00', Iban::format('nl91abna0417164300'));
    }

    public function test_batch_is_built_from_open_invoices_of_customers_with_a_mandate(): void
    {
        $user = $this->demoUser();
        $user->company->forceFill(['iban' => 'NL91ABNA0417164300', 'sepa_creditor_id' => 'NL12ZZZ123456780000'])->save();
        $this->actingAs($user);

        $invoice = Invoice::regular()->whereIn('status', ['sent', 'overdue'])->whereNotNull('customer_id')->firstOrFail();
        $customer = Customer::findOrFail($invoice->customer_id);

        // Machtiging vastleggen via het klantformulier → kenmerk wordt automatisch gemaakt.
        $this->put(route('customers.update', $customer), array_merge(
            $customer->only(['name', 'type', 'email', 'address_line', 'postal_code', 'city', 'country', 'payment_terms', 'language']),
            ['mandate_iban' => 'NL91 ABNA 0417 1643 00', 'mandate_signed_on' => '2026-01-15']
        ))->assertRedirect()->assertSessionHasNoErrors();
        $customer->refresh();
        $this->assertSame('active', $customer->mandate_status);
        $this->assertStringStartsWith('EI', $customer->mandate_reference);
        $this->assertSame('NL91ABNA0417164300', $customer->mandate_iban);

        $this->get(route('direct-debit.index'))->assertOk();
        $collectable = app(SepaDirectDebitService::class)->collectable($user->company);
        $this->assertTrue($collectable->contains('id', $invoice->id));

        $date = SepaDirectDebitService::earliestCollectionDate()->toDateString();
        $this->post(route('direct-debit.store'), ['invoice_ids' => [$invoice->id], 'collection_date' => $date])->assertRedirect()->assertSessionMissing('error');

        $batch = DirectDebitBatch::firstOrFail();
        $this->assertSame(1, $batch->count);
        $this->assertEqualsWithDelta((float) $invoice->remaining_amount, (float) $batch->total, 0.001);
        $this->assertSame($batch->id, $invoice->fresh()->direct_debit_batch_id);
        $this->assertSame('FRST', $batch->lines[0]['sequence']);

        $xml = $this->get(route('direct-debit.download', $batch))->assertOk()->getContent();
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml));
        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('p', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');
        $this->assertSame('1', $xp->evaluate('string(//p:GrpHdr/p:NbOfTxs)'));
        $this->assertSame('NL12ZZZ123456780000', $xp->evaluate('string(//p:CdtrSchmeId//p:Othr/p:Id)'));
        $this->assertSame('CORE', $xp->evaluate('string(//p:LclInstrm/p:Cd)'));
        $this->assertSame('FRST', $xp->evaluate('string(//p:SeqTp)'));
        $this->assertSame('NL91ABNA0417164300', $xp->evaluate('string(//p:DbtrAcct//p:IBAN)'));
        $this->assertSame($customer->mandate_reference, $xp->evaluate('string(//p:MndtId)'));
        $this->assertSame(number_format((float) $batch->total, 2, '.', ''), $xp->evaluate('string(//p:InstdAmt)'));

        // Volgende batch voor dezelfde klant is een vervolg-incasso; geannuleerde batch geeft de factuur vrij.
        $this->assertNotNull($customer->fresh()->mandate_first_collected_at);
        $this->delete(route('direct-debit.destroy', $batch))->assertRedirect();
        $this->assertNull($invoice->fresh()->direct_debit_batch_id);
        $this->assertSame(0, DirectDebitBatch::count());
    }

    public function test_batch_is_refused_without_creditor_id(): void
    {
        $user = $this->demoUser();
        $user->company->forceFill(['iban' => 'NL91ABNA0417164300', 'sepa_creditor_id' => null])->save();
        $this->actingAs($user);

        $this->post(route('direct-debit.store'), ['invoice_ids' => [1], 'collection_date' => SepaDirectDebitService::earliestCollectionDate()->toDateString()])
            ->assertRedirect()->assertSessionHas('error');
    }
}
