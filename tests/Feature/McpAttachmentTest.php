<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Support\RemoteFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Claude-koppeling: een echt bestand (pdf) als bijlage bij een concept — via
 * een openbare link die de app zelf ophaalt, of via een eenmalige upload-link
 * (15 oktober 2026: Claude kon in claude.ai een zelfgemaakte pdf niet als
 * base64 door het gesprek krijgen).
 */
class McpAttachmentTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private const PDF = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";

    protected function tearDown(): void
    {
        RemoteFile::$resolver = null;
        parent::tearDown();
    }

    private function company(): Company
    {
        $company = $this->demoUser()->company;
        $company->forceFill(['is_exempt' => true])->save();
        $company->rotateMcpToken();

        return $company->fresh();
    }

    /** Roep een MCP-tool aan en geef het result-blok terug. */
    private function tool(Company $company, string $name, array $arguments): array
    {
        return $this->postJson(route('mcp', $company->mcp_token), [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ])->assertOk()->json('result');
    }

    private function uploadUrl(string $text): string
    {
        $this->assertMatchesRegularExpression('#https?://\S+/mcp/upload/\S+#', $text);
        preg_match('#https?://\S+/mcp/upload/\S+#', $text, $m);

        return rtrim($m[0], '"');
    }

    private function lines(): array
    {
        return [['omschrijving' => 'Advies', 'aantal' => 1, 'prijs_excl_btw' => 100, 'btw_percentage' => 21]];
    }

    public function test_the_tool_list_offers_bijlage_uploaden(): void
    {
        $company = $this->company();

        $tools = $this->postJson(route('mcp', $company->mcp_token), ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertOk()->json('result.tools');

        $this->assertContains('bijlage_uploaden', array_column($tools, 'name'));
    }

    public function test_a_quote_gets_a_file_fetched_from_a_public_url(): void
    {
        $company = $this->company();
        $customer = Customer::where('company_id', $company->id)->orderBy('id')->firstOrFail();
        RemoteFile::$resolver = fn () => ['93.184.216.34'];
        Http::fake(['https://files.example.com/*' => Http::response(self::PDF, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="plan.pdf"',
        ])]);

        $result = $this->tool($company, 'offerte_aanmaken', [
            'klant' => $customer->name, 'regels' => $this->lines(),
            'bijlage' => ['url' => 'https://files.example.com/download/123'],
        ]);

        $this->assertFalse($result['isError']);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Bijlage "plan.pdf" toegevoegd', $text);
        $quote = Quote::where('company_id', $company->id)->latest('id')->firstOrFail();
        $this->assertStringContainsString("offerte_id: {$quote->id}", $text);

        $attachment = Attachment::where('attachable_type', Quote::class)->where('attachable_id', $quote->id)->firstOrFail();
        $this->assertSame('plan.pdf', $attachment->filename);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame($company->id, $attachment->company_id);
        $this->assertTrue($attachment->for_customer);
        $this->assertSame(self::PDF, $attachment->contents());
    }

    public function test_a_redirect_is_followed_and_the_name_comes_from_the_path(): void
    {
        $company = $this->company();
        $quote = Quote::where('company_id', $company->id)->orderBy('id')->firstOrFail();
        RemoteFile::$resolver = fn () => ['93.184.216.34'];
        Http::fake([
            'https://share.example.com/x' => Http::response('', 302, ['Location' => 'https://cdn.example.com/files/offerte%20dak.pdf']),
            'https://cdn.example.com/*' => Http::response(self::PDF, 200, ['Content-Type' => 'application/pdf']),
        ]);

        $result = $this->tool($company, 'bijlage_uploaden', ['offerte_id' => $quote->id, 'url' => 'https://share.example.com/x']);

        $this->assertFalse($result['isError'], $result['content'][0]['text']);
        $attachment = Attachment::where('attachable_type', Quote::class)->where('attachable_id', $quote->id)->latest('id')->firstOrFail();
        $this->assertSame('offerte dak.pdf', $attachment->filename);
    }

    public function test_private_and_invalid_addresses_are_refused(): void
    {
        $company = $this->company();
        $quote = Quote::where('company_id', $company->id)->orderBy('id')->firstOrFail();
        Http::fake();

        foreach (['http://127.0.0.1/geheim.pdf', 'http://localhost/x.pdf', 'https://[::1]/x.pdf'] as $url) {
            $result = $this->tool($company, 'bijlage_uploaden', ['offerte_id' => $quote->id, 'url' => $url]);
            $this->assertTrue($result['isError'], $url);
            $this->assertStringContainsString('Alleen openbare adressen', $result['content'][0]['text'], $url);
        }

        $result = $this->tool($company, 'bijlage_uploaden', ['offerte_id' => $quote->id, 'url' => 'ftp://files.example.com/x.pdf']);
        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('openbare https-link', $result['content'][0]['text']);

        // Een nette hostnaam die naar een intern adres wijst: ook geweigerd.
        RemoteFile::$resolver = fn () => ['10.0.0.8'];
        $result = $this->tool($company, 'bijlage_uploaden', ['offerte_id' => $quote->id, 'url' => 'https://intranet.example.com/x.pdf']);
        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('Alleen openbare adressen', $result['content'][0]['text']);

        Http::assertNothingSent();
        $this->assertSame(0, Attachment::where('attachable_type', Quote::class)->where('attachable_id', $quote->id)->count());

        // Zonder (of met twee) id's: duidelijke fout.
        $result = $this->tool($company, 'bijlage_uploaden', ['url' => 'https://files.example.com/x.pdf']);
        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('offerte_id', $result['content'][0]['text']);
    }

    public function test_an_upload_link_accepts_the_file_once(): void
    {
        $company = $this->company();
        $invoice = Invoice::where('company_id', $company->id)->where('status', 'draft')->orderBy('id')->firstOrFail();

        $result = $this->tool($company, 'bijlage_uploaden', ['factuur_id' => $invoice->id, 'bestandsnaam' => 'urenstaat.pdf']);
        $this->assertFalse($result['isError'], $result['content'][0]['text']);
        $url = $this->uploadUrl($result['content'][0]['text']);
        $this->assertStringContainsString('signature=', $url);

        // Ruwe bytes via PUT.
        $this->call('PUT', $url, [], [], [], ['CONTENT_TYPE' => 'application/pdf'], self::PDF)
            ->assertStatus(201)
            ->assertJson(['ok' => true, 'bestand' => 'urenstaat.pdf']);

        $attachment = Attachment::where('attachable_type', Invoice::class)->where('attachable_id', $invoice->id)->latest('id')->firstOrFail();
        $this->assertSame('urenstaat.pdf', $attachment->filename);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame($company->id, $attachment->company_id);
        $this->assertTrue($attachment->for_customer);
        $this->assertSame(self::PDF, $attachment->contents());

        // Dezelfde link een tweede keer: geweigerd.
        $this->call('PUT', $url, [], [], [], ['CONTENT_TYPE' => 'application/pdf'], self::PDF)
            ->assertStatus(410)->assertJson(['ok' => false]);

        // Een vervalste handtekening: geweigerd.
        $this->call('PUT', str_replace('signature=', 'signature=00', $url), [], [], [], ['CONTENT_TYPE' => 'application/pdf'], self::PDF)
            ->assertStatus(403)->assertJson(['ok' => false]);
    }

    public function test_a_wrong_file_type_keeps_the_link_usable_and_multipart_works(): void
    {
        $company = $this->company();
        $quote = Quote::where('company_id', $company->id)->orderBy('id')->firstOrFail();
        $url = $this->uploadUrl($this->tool($company, 'bijlage_uploaden', ['offerte_id' => $quote->id])['content'][0]['text']);

        $this->call('PUT', $url, [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'gewoon wat tekst')
            ->assertStatus(422)->assertJson(['ok' => false]);
        $this->call('PUT', $url, [], [], [], ['CONTENT_TYPE' => 'application/pdf'], '')
            ->assertStatus(422)->assertJson(['ok' => false]);

        $file = UploadedFile::fake()->createWithContent('offerte.pdf', self::PDF);
        $this->call('POST', $url, [], [], ['file' => $file])
            ->assertStatus(201)
            ->assertJson(['ok' => true, 'bestand' => 'offerte.pdf']);

        $attachment = Attachment::where('attachable_type', Quote::class)->where('attachable_id', $quote->id)->latest('id')->firstOrFail();
        $this->assertSame(self::PDF, $attachment->contents());
    }

    public function test_a_link_only_works_for_a_document_of_the_same_company(): void
    {
        $company = $this->company();
        $quote = Quote::where('company_id', $company->id)->orderBy('id')->firstOrFail();
        $url = $this->uploadUrl($this->tool($company, 'bijlage_uploaden', ['offerte_id' => $quote->id])['content'][0]['text']);

        // De koppeling intrekken maakt ook de open upload-link waardeloos.
        $company->forceFill(['mcp_token' => null])->save();
        $this->call('PUT', $url, [], [], [], ['CONTENT_TYPE' => 'application/pdf'], self::PDF)
            ->assertStatus(403)->assertJson(['ok' => false]);

        // Andermans document bestaat voor deze administratie niet.
        $result = $this->tool($this->company(), 'bijlage_uploaden', ['offerte_id' => 999999]);
        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('staat niet in deze administratie', $result['content'][0]['text']);
    }
}
