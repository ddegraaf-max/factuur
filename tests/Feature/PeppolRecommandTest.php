<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PurchaseInboxItem;
use App\Services\PeppolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Peppol via Recommand, met een nagebootste API: registreren, verzenden, ontvangen. */
class PeppolRecommandTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.peppol.recommand_key' => 'key', 'services.peppol.recommand_secret' => 'secret', 'services.peppol.recommand_webhook_secret' => 'whsec']);
    }

    public function test_company_registers_and_sends_an_invoice(): void
    {
        Http::fake([
            'app.recommand.eu/api/v1/companies/c_1/identifiers' => Http::response(['success' => true, 'identifiers' => [['scheme' => '0106', 'identifier' => '12345678']]]),
            'app.recommand.eu/api/v1/companies/c_1' => Http::response(['success' => true, 'company' => ['id' => 'c_1', 'isVerified' => true]]),
            // GET = zoeken naar een bestaande registratie (niets gevonden), POST = aanmaken.
            'app.recommand.eu/api/v1/companies*' => fn ($request) => $request->method() === 'GET'
                ? Http::response(['success' => true, 'companies' => []])
                : Http::response(['success' => true, 'company' => ['id' => 'c_1', 'isVerified' => false], 'verificationUrl' => 'https://app.recommand.eu/verify/abc']),
            'app.recommand.eu/api/v1/webhooks' => Http::response(['success' => true, 'webhooks' => []]),
            'app.recommand.eu/api/v1/verify' => Http::response(['success' => true, 'isValid' => true, 'supportedDocuments' => [['docTypeId' => PeppolService::DOCTYPE_BIS3]]]),
            'app.recommand.eu/api/v1/c_1/send' => Http::response(['success' => true, 'id' => 'doc_out', 'sentOverPeppol' => true]),
        ]);

        $user = $this->demoUser();
        $this->actingAs($user);
        $company = $user->company;

        $this->post(route('settings.integrations.peppol.activate'))->assertRedirect();
        $company->refresh();
        $this->assertSame('c_1', $company->peppol_company_id);
        $this->assertSame('pending', $company->peppol_verification_status);
        $this->assertSame('https://app.recommand.eu/verify/abc', $company->peppol_verification_url);

        $this->post(route('settings.integrations.peppol.refresh'))->assertRedirect();
        $this->assertSame('verified', $company->fresh()->peppol_verification_status);

        $invoice = Invoice::regular()->where('status', 'sent')->whereNotNull('customer_id')->firstOrFail();
        $invoice->customer->forceFill(['kvk_number' => '12345678', 'peppol_id' => null])->save();
        $this->post(route('invoices.peppol.send', $invoice))->assertRedirect();

        $invoice->refresh();
        $this->assertSame('doc_out', $invoice->peppol_reference);
        $this->assertNotNull($invoice->peppol_sent_at);

        // Ontvanger accepteert alleen BIS 3 → customization-ID omgezet, XML als string meegestuurd.
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/c_1/send')
            && $request['documentType'] === 'xml'
            && str_contains($request['document'], 'urn:fdc:peppol.eu:2017:poacc:billing:3.0')
            && str_starts_with($request['recipient'], '0106:'));
    }

    /** Bedrijf al handmatig in het Recommand-dashboard aangemaakt → koppelen, niet dubbel registreren. */
    public function test_activation_adopts_an_existing_recommand_company(): void
    {
        Http::fake([
            'app.recommand.eu/api/v1/companies/c_9/identifiers' => fn ($request) => $request->method() === 'GET'
                ? Http::response(['success' => true, 'identifiers' => [['scheme' => '9944', 'identifier' => 'NL001234567B01']]])
                : Http::response(['success' => true, 'identifier' => ['id' => 'i_1']]),
            'app.recommand.eu/api/v1/companies*' => fn ($request) => $request->method() === 'GET'
                ? Http::response(['success' => true, 'companies' => [['id' => 'c_9', 'name' => 'Bestaand BV', 'enterpriseNumber' => '', 'vatNumber' => 'NL001234567B01', 'isVerified' => true]]])
                : Http::response(['success' => false, 'error' => 'mag niet aangeroepen worden'], 500),
            'app.recommand.eu/api/v1/webhooks' => Http::response(['success' => true, 'webhooks' => []]),
        ]);

        $user = $this->demoUser();
        $this->actingAs($user);
        $company = $user->company;
        $company->forceFill(['kvk_number' => '87654321', 'vat_number' => 'NL001234567B01'])->save();

        $this->post(route('settings.integrations.peppol.activate'))->assertRedirect()->assertSessionMissing('error');

        $company->refresh();
        $this->assertSame('c_9', $company->peppol_company_id);
        $this->assertSame('verified', $company->peppol_verification_status);
        $this->assertNotNull($company->peppol_verified_at);

        // Geen POST /companies (geen dubbele registratie); wél de KvK als 0106-identifier toegevoegd.
        Http::assertNotSent(fn ($request) => $request->method() === 'POST' && str_ends_with($request->url(), '/api/v1/companies'));
        Http::assertSent(fn ($request) => $request->method() === 'POST' && str_ends_with($request->url(), '/companies/c_9/identifiers')
            && $request['scheme'] === '0106' && $request['identifier'] === '87654321');
    }

    public function test_webhook_imports_received_invoice_into_inbox(): void
    {
        $user = $this->demoUser();
        $user->company->forceFill(['peppol_company_id' => 'c_9', 'peppol_verification_status' => 'verified'])->save();

        Http::fake([
            'app.recommand.eu/api/v1/documents/doc_in/render/pdf' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
            'app.recommand.eu/api/v1/documents/doc_in/mark-as-read' => Http::response(['success' => true]),
            'app.recommand.eu/api/v1/documents/doc_in' => Http::response(['success' => true, 'document' => [
                'id' => 'doc_in', 'companyId' => 'c_9', 'direction' => 'incoming', 'type' => 'invoice',
                'parsed' => [
                    'invoiceNumber' => 'LEV-2026-77', 'issueDate' => '2026-08-20', 'dueDate' => '2026-09-19',
                    'seller' => ['name' => 'Bouwmaat B.V.'],
                    'totals' => ['taxExclusiveAmount' => '100.00', 'taxInclusiveAmount' => '121.00', 'payableAmount' => '121.00'],
                    'vat' => ['subtotals' => [['taxableAmount' => '100.00', 'vatAmount' => '21.00', 'percentage' => '21.00']]],
                ],
            ]]),
        ]);

        $body = json_encode(['eventType' => 'document.received', 'documentId' => 'doc_in', 'teamId' => 'team_1', 'companyId' => 'c_9']);
        $signature = 'sha256=' . hash_hmac('sha256', $body, 'whsec');

        $this->call('POST', route('recommand.webhook'), [], [], [], ['HTTP_X_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

        $item = PurchaseInboxItem::withoutGlobalScope('company')->where('peppol_document_id', 'doc_in')->firstOrFail();
        $this->assertSame($user->company_id, $item->company_id);
        $this->assertSame('application/pdf', $item->mime_type);
        $this->assertSame('Bouwmaat B.V.', $item->scan['supplier_name']);
        $this->assertSame('LEV-2026-77', $item->scan['supplier_reference']);
        $this->assertEquals(21.0, (float) $item->scan['vat_lines'][0]['vat']);
        $this->assertEquals(121.0, (float) $item->scan['total_incl']);

        // Nogmaals dezelfde webhook → geen dubbel item.
        $this->call('POST', route('recommand.webhook'), [], [], [], ['HTTP_X_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $this->assertSame(1, PurchaseInboxItem::withoutGlobalScope('company')->where('peppol_document_id', 'doc_in')->count());
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        $body = json_encode(['eventType' => 'document.received', 'documentId' => 'doc_x']);
        $this->call('POST', route('recommand.webhook'), [], [], [], ['HTTP_X_SIGNATURE' => 'sha256=nope', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(401);
    }
}
