<?php

namespace Tests\Feature;

use App\Models\BrandProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Support\DocumentLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Poolse (en Engelse) documenten (1.53.0): de voetnoot volgt de documenttaal
 * (vertaling uit Instellingen, anders de standaard), het PDF gebruikt
 * DejaVu-lettertypen zodat ł, ś, ą, ę en ż geen vraagtekens worden, en het
 * registratienummer draagt het label van het land van het bedrijf.
 */
class DocumentFooterLanguageTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function lines(): array
    {
        return [[
            'description' => 'Doradztwo', 'quantity' => 1, 'unit' => 'godz.',
            'unit_price' => 100, 'vat_rate' => 21, 'discount_pct' => 0,
        ]];
    }

    public function test_footer_follows_the_document_language_and_polish_pdf_uses_dejavu_fonts(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $company = $user->company;
        $company->forceFill([
            'invoice_footer' => 'Bedankt voor uw vertrouwen!',
            'invoice_footers' => ['pl' => 'Dziękujemy za zaufanie!'],
            'invoice_font' => 'serif', 'invoice_template' => 'modern',
        ])->save();
        $customer = Customer::orderBy('id')->firstOrFail();
        $base = ['customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'payment_terms' => 14, 'lines' => $this->lines(), 'action' => 'draft'];

        $this->post(route('invoices.store'), $base + ['reference' => 'Stopka', 'language' => 'pl'])->assertRedirect();
        $invoice = Invoice::where('reference', 'Stopka')->firstOrFail();
        $this->assertSame('Dziękujemy za zaufanie!', $invoice->footer);

        // Terug naar Nederlands: de standaard voetnoot; Engels zonder vertaling ook.
        $this->put(route('invoices.update', $invoice), $base + ['language' => 'nl'])->assertRedirect();
        $this->assertSame('Bedankt voor uw vertrouwen!', $invoice->fresh()->footer);
        $this->put(route('invoices.update', $invoice), $base + ['language' => 'en'])->assertRedirect();
        $this->assertSame('Bedankt voor uw vertrouwen!', $invoice->fresh()->footer);

        // Pools PDF met schreefletter: DejaVu Serif en DejaVu Sans Mono, geen Times/Courier.
        $this->put(route('invoices.update', $invoice), $base + ['language' => 'pl'])->assertRedirect();
        $pdf = $this->get(route('invoices.pdf', $invoice))->assertOk()->getContent();
        $this->assertStringContainsString('DejaVuSerif', $pdf);
        $this->assertStringContainsString('DejaVuSansMono', $pdf);
        $this->assertStringNotContainsString('Times-Roman', $pdf);

        // Schreefloos: DejaVu Sans, ook in het Nederlands (stond eerder per ongeluk in Times).
        $company->forceFill(['invoice_font' => 'sans'])->save();
        $this->put(route('invoices.update', $invoice), $base + ['language' => 'nl'])->assertRedirect();
        $pdf = $this->get(route('invoices.pdf', $invoice))->assertOk()->getContent();
        $this->assertStringContainsString('DejaVuSans', $pdf);
        $this->assertStringNotContainsString('Times-Roman', $pdf);
    }

    public function test_translated_footer_of_brand_profile_and_company_take_precedence_in_order(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $company = $user->company;
        $company->forceFill(['invoice_footer' => 'Bedrijf NL', 'invoice_footers' => ['pl' => 'Firma PL']])->save();
        $profile = BrandProfile::create(['company_id' => $company->id, 'name' => 'Tweede naam', 'invoice_footer' => 'Handelsnaam NL']);

        $this->assertSame('Handelsnaam NL', $company->documentFooter($profile, 'nl'));
        $this->assertSame('Firma PL', $company->documentFooter($profile, 'pl'), 'Vertaling van het bedrijf gaat vóór de standaardtekst van de handelsnaam');
        $profile->update(['invoice_footers' => ['pl' => 'Marka PL']]);
        $this->assertSame('Marka PL', $company->documentFooter($profile->fresh(), 'pl'));
        $this->assertSame('Handelsnaam NL', $company->documentFooter($profile->fresh(), 'en'));
        $this->assertSame('Bedrijf NL', $company->documentFooter(null, 'en'));
    }

    public function test_registry_label_follows_the_country_and_helpers_clean_input(): void
    {
        $this->assertSame('KVK', DocumentLocale::registryLabel('NL'));
        $this->assertSame('REGON', DocumentLocale::registryLabel('PL'));
        $this->assertSame('KVK', DocumentLocale::using('pl', fn () => DocumentLocale::registryLabel('nl')));
        $this->assertSame('CoC', DocumentLocale::using('en', fn () => DocumentLocale::registryLabel('BE')));
        $this->assertSame('Nr rej.', DocumentLocale::using('pl', fn () => DocumentLocale::registryLabel('DE')));

        $this->assertStringContainsString('DejaVu Serif', DocumentLocale::using('pl', fn () => DocumentLocale::font('serif')));
        $this->assertStringContainsString('Georgia', DocumentLocale::using('nl', fn () => DocumentLocale::font('serif')));
        $this->assertStringContainsString('DejaVu Sans', DocumentLocale::using('nl', fn () => DocumentLocale::font('sans')));

        $this->assertSame(['pl' => 'Tekst'], DocumentLocale::cleanFooters(['pl' => ' Tekst ', 'en' => '', 'de' => 'x']));
        $this->assertNull(DocumentLocale::cleanFooters(['en' => '']));
        $this->assertNull(DocumentLocale::cleanFooters('tekst'));
    }
}
