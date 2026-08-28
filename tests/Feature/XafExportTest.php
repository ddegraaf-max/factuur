<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\XafExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Auditfile XAF 3.2: geldig tegen het officiële XSD-schema en sluitend in debet/credit. */
class XafExportTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_auditfile_validates_against_the_official_xsd_and_balances(): void
    {
        $user = $this->demoUser();
        $latest = Invoice::regular()->where('status', '!=', 'draft')->max('invoice_date');
        $year = $latest ? \Carbon\Carbon::parse($latest)->year : now()->year;

        $xml = app(XafExporter::class)->generate($user->company, $year);

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'geen geldige XML');
        libxml_use_internal_errors(true);
        $valid = $doc->schemaValidate(base_path('tests/Support/fixtures/XmlAuditfileFinancieel3.2.xsd'));
        $errors = collect(libxml_get_errors())->map(fn ($e) => trim($e->message) . " (regel {$e->line})")->take(5)->implode("\n");
        libxml_clear_errors();
        $this->assertTrue($valid, "XAF voldoet niet aan het XSD:\n{$errors}");

        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('x', 'http://www.auditfiles.nl/XAF/3.2');
        $debit = (float) $xp->evaluate('string(//x:transactions/x:totalDebit)');
        $credit = (float) $xp->evaluate('string(//x:transactions/x:totalCredit)');
        $lines = (int) $xp->evaluate('string(//x:transactions/x:linesCount)');
        $this->assertGreaterThan(0, $lines);
        $this->assertSame($lines, $xp->query('//x:trLine')->length);
        $this->assertEqualsWithDelta($debit, $credit, 0.011, 'debet en credit sluiten niet');
        $this->assertSame($user->company->name, $xp->evaluate('string(//x:company/x:companyName)'));
        $this->assertGreaterThan(0, $xp->query('//x:customerSupplier[x:custSupTp="C"]')->length);
    }

    public function test_auditfile_downloads_as_attachment(): void
    {
        $this->actingAs($this->demoUser());

        $this->get(route('export.xaf', ['year' => now()->year]))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('auditfiles.nl/XAF/3.2', false);
        $this->get(route('export.xaf', ['year' => 1999]))->assertSessionHasErrors('year');
    }
}
