<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use App\Models\RecurringInvoice;
use App\Models\ReminderLog;
use App\Models\TimeEntry;
use App\Models\Trip;
use App\Models\VatFiling;
use App\Services\DemoDataBuilder;
use App\Services\NipService;
use App\Support\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * De demo-sandbox volgt de markt: onder Lopra Polska bouwt DemoDataBuilder een
 * Poolse voorbeeldadministratie (Studio Wnętrz Kowalska — PLN, btw 23/8%, NIP,
 * FV-nummers, factuur te koop bij sprzedamfakture.pl), en de Nederlandse demo
 * (Jansen Webdesign) blijft precies zoals hij was.
 */
class DemoPlTest extends TestCase
{
    use RefreshDatabase;

    public function test_polish_brand_gets_a_polish_demo(): void
    {
        config(['brand.active' => 'lopra_pl']);
        $this->assertTrue(Market::isPl());

        $user = app(DemoDataBuilder::class)->build();
        $company = $user->company;
        $year = now()->year;

        // --- Bedrijf en gebruiker ---
        $this->assertSame('Studio Wnętrz Kowalska', $company->name);
        $this->assertSame('Anna Kowalska', $user->name);
        $this->assertStringEndsWith('@lopra.pl', $user->email);
        $this->assertSame('PL', $company->country);
        $this->assertSame('PLN', $company->currency);
        $this->assertTrue(NipService::valid($company->vat_number), "NIP {$company->vat_number} van het demobedrijf heeft geen geldig controlecijfer");
        $this->assertMatchesRegularExpression('/^\d{9}$/', $company->kvk_number, 'REGON: negen cijfers');
        $this->assertStringStartsWith('PL', $company->iban);
        $this->assertSame('FV/{year}/{sequence:4}', $company->invoice_number_format);
        $this->assertSame(14, $company->default_payment_terms);
        $this->assertSame(Market::get('invoice_footer'), $company->invoice_footer);
        $this->assertSame('#2E4A3F', $company->brand_color);
        $this->assertSame('#D9A066', $company->accent_color);
        $this->assertEqualsWithDelta((float) Market::get('km_rate'), (float) $company->default_km_rate, 0.001);
        $this->assertNull($company->fresh()->ob_number, 'Een Nederlands omzetbelastingnummer hoort niet in de Poolse demo');

        // --- Klanten: Poolse adressen, REGON, NIP met geldig controlecijfer, taal pl ---
        $customers = Customer::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(8, $customers->count());
        foreach ($customers as $customer) {
            $this->assertTrue(NipService::valid($customer->vat_number), "Klant {$customer->name}: NIP {$customer->vat_number} is ongeldig");
            $this->assertMatchesRegularExpression('/^\d{9}$/', (string) $customer->kvk_number, "Klant {$customer->name}: REGON");
            $this->assertSame('pl', $customer->language);
            $this->assertSame('PL', $customer->country);
            $this->assertMatchesRegularExpression('/^\d{2}-\d{3}$/', $customer->postal_code, "Klant {$customer->name}: Poolse postcode");
            $this->assertStringEndsWith('.pl', $customer->email);
        }
        $this->assertTrue($customers->contains('city', 'Kraków'));
        $this->assertTrue($customers->contains('city', 'Warszawa'));

        // --- Producten in het Pools, alleen Poolse tarieven ---
        $products = Product::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertCount(6, $products);
        foreach ($products as $product) {
            $this->assertContains((int) $product->vat_rate, Market::vatRates());
        }
        $this->assertTrue($products->contains('name', 'Nadzór autorski'));
        $this->assertFalse($products->contains(fn ($p) => str_contains($p->name, 'Website')));

        // --- Facturen: dezelfde statusverdeling als de Nederlandse demo, nummers FV/… ---
        $invoices = Invoice::withoutGlobalScopes()->where('company_id', $company->id)->get();
        foreach (['paid', 'partial', 'sent', 'overdue', 'draft'] as $status) {
            $this->assertTrue($invoices->contains('status', $status), "Geen factuur met status {$status}");
        }
        $this->assertSame(7, $invoices->where('status', 'paid')->count());
        $this->assertTrue($invoices->contains('is_credit', true), 'Geen creditnota');
        foreach ($invoices->where('status', '!=', 'draft') as $invoice) {
            $this->assertStringStartsWith('FV/', (string) $invoice->number, "Factuur {$invoice->reference} heeft geen FV-nummer");
        }
        $this->assertNull($invoices->firstWhere('status', 'draft')->number);
        $this->assertTrue($invoices->contains('number', "FV/{$year}/0031"));
        $this->assertFalse($invoices->contains(fn ($i) => str_contains((string) $i->reference, 'Opdracht') || str_contains((string) $i->notes, 'factuur')), 'Nederlandse tekst in de Poolse demo');
        foreach ($invoices as $invoice) {
            $this->assertSame(Market::get('invoice_footer'), $invoice->footer);
        }

        // --- Regels: alleen 23/8/5/0, met ten minste één 8%-regel; Poolse eenheden ---
        $lines = InvoiceLine::withoutGlobalScopes()->whereIn('invoice_id', $invoices->pluck('id'))->get();
        $this->assertNotEmpty($lines);
        foreach ($lines as $line) {
            $this->assertContains((int) $line->vat_rate, [23, 8, 5, 0], "Regel '{$line->description}' heeft tarief {$line->vat_rate}");
        }
        $this->assertTrue($lines->contains(fn ($l) => (int) $l->vat_rate === 23));
        $this->assertTrue($lines->contains(fn ($l) => (int) $l->vat_rate === 8));
        $this->assertTrue($lines->contains('description', 'Nadzór autorski'));
        $this->assertFalse($lines->contains('unit', 'stuk'));

        // --- Windykacja: vervallen facturen, waarvan één te koop aangeboden aan sprzedamfakture.pl;
        //     geen incassodossier (Polen heeft geen incassopartner) ---
        $overdue = $invoices->where('status', 'overdue');
        $this->assertTrue(
            $overdue->contains(fn ($i) => $i->due_date->lt(now()->subDays(30))),
            'Geen vervallen factuur die ≥ 30 dagen over de termijn is'
        );
        $this->assertCount(0, $invoices->where('status', 'incasso'), 'Poolse demo mag geen incassodossier hebben');
        $offered = $invoices->whereNotNull('sale_requested_at');
        $this->assertCount(1, $offered);
        $this->assertSame('overdue', $offered->first()->status);
        $this->assertNull($offered->first()->incasso_handler);
        $trail = ReminderLog::withoutGlobalScopes()->where('invoice_id', $offered->first()->id)->pluck('type');
        $this->assertContains('Pierwsze przypomnienie', $trail->all());
        $this->assertContains('Ostateczne wezwanie do zapłaty', $trail->all());

        // --- Betalingen: toegestane betaalwijzen, Poolse referenties ---
        $payments = Payment::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertCount(8, $payments);
        foreach ($payments as $payment) {
            $this->assertContains($payment->method, ['bank_transfer', 'ideal', 'cash', 'card', 'other']);
        }
        $this->assertTrue($payments->contains(fn ($p) => str_starts_with((string) $p->reference, 'Przelew FV/')));
        $this->assertTrue($payments->contains('reference', 'Zaliczka BLIK'));

        // --- Offertes, terugkerende facturen, uren, ritten, inkoop, btw-tijdvak ---
        $quotes = Quote::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertCount(3, $quotes);
        foreach ($quotes as $quote) {
            $this->assertStringStartsWith('OF/', $quote->number);
        }
        $this->assertSame(2, RecurringInvoice::withoutGlobalScopes()->where('company_id', $company->id)->count());

        $hours = TimeEntry::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(5, $hours->count());
        $this->assertTrue($hours->contains(fn ($h) => $h->billable && $h->customer_id && ! $h->invoice_id));
        $this->assertSame($user->id, $hours->first()->user_id);

        $trips = Trip::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(3, $trips->count());
        $this->assertTrue($trips->contains(fn ($t) => str_contains($t->to_location, 'Katowice')));

        $purchases = PurchaseInvoice::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(4, $purchases->count());
        $this->assertTrue($purchases->contains(fn ($p) => str_starts_with($p->supplier_name, 'Castorama')));
        $this->assertTrue($purchases->contains('status', 'open'));
        $this->assertTrue($purchases->contains('status', 'paid'));
        foreach ($purchases as $purchase) {
            foreach ($purchase->vat_lines as $vatLine) {
                $this->assertSame(23, (int) $vatLine['rate']);
            }
            $this->assertEqualsWithDelta((float) $purchase->subtotal + (float) $purchase->vat_total, (float) $purchase->total, 0.01);
        }

        $filing = VatFiling::withoutGlobalScopes()->where('company_id', $company->id)->first();
        $this->assertNotNull($filing);
        $this->assertSame('month', $filing->period_type, 'JPK_V7M: de Poolse aangifte gaat per maand');
    }

    public function test_dutch_demo_is_untouched(): void
    {
        $this->assertFalse(Market::isPl());

        $user = app(DemoDataBuilder::class)->build();
        $company = $user->company;
        $year = now()->year;

        $this->assertSame('Jansen Webdesign', $company->name);
        $this->assertSame('Jan Jansen', $user->name);
        $this->assertSame('EUR', $company->currency);
        $this->assertSame('NL', $company->country);
        $this->assertSame('{year}-{sequence:4}', $company->invoice_number_format);
        $this->assertMatchesRegularExpression('/^NL\d{9}B\d{2}$/', $company->vat_number);
        $this->assertMatchesRegularExpression('/^\d{8}$/', $company->kvk_number);
        $this->assertNotNull($company->fresh()->ob_number);

        $customers = Customer::withoutGlobalScopes()->where('company_id', $company->id)->orderBy('id')->get();
        $this->assertCount(6, $customers);
        $this->assertSame('De Vries Bouw B.V.', $customers->first()->name);
        $this->assertSame('nl', $customers->first()->language);
        $this->assertSame('NL', $customers->first()->country);

        $invoices = Invoice::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertTrue($invoices->contains('number', "{$year}-0031"));
        $this->assertTrue($invoices->contains('number', "C{$year}-0001"));
        $this->assertTrue($invoices->contains('reference', "Opdracht {$year}-100"));
        $this->assertSame('Armaere Gerechtsdeurwaarders', $invoices->firstWhere('status', 'incasso')->incasso_handler);

        $lines = InvoiceLine::withoutGlobalScopes()->whereIn('invoice_id', $invoices->pluck('id'))->get();
        foreach ($lines as $line) {
            $this->assertSame(21, (int) $line->vat_rate);
            $this->assertSame('stuk', $line->unit);
        }

        $quotes = Quote::withoutGlobalScopes()->where('company_id', $company->id)->get();
        $this->assertTrue($quotes->contains('number', "OFF-{$year}-0001"));

        // De Poolse extra's (uren, ritten, inkoop) horen niet in de Nederlandse demo.
        $this->assertSame(0, TimeEntry::withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertSame(0, Trip::withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertSame(0, PurchaseInvoice::withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertSame('quarter', VatFiling::withoutGlobalScopes()->where('company_id', $company->id)->first()->period_type);
    }
}
