<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Rookproef: elke pagina moet renderen. Vangt de klassieke productiefouten
 * (ontbrekende import, verkeerde routenaam, prop die niet meegaat, kapotte
 * Blade-variabele) vóórdat ze live gaan.
 */
class SmokePagesTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_public_pages_render(): void
    {
        $paths = ['/', '/over-ons', '/contact', '/veelgestelde-vragen', '/helpcentrum', '/roadmap',
            '/wat-is-nieuw', '/status', '/privacy', '/voorwaarden', '/cookies', '/demo', '/login', '/register'];
        foreach ($paths as $path) {
            $this->assertRenders($path);
        }

        foreach (['portal.login', 'kennisbank', 'ai', 'boekhouders', 'gratis-factuur'] as $name) {
            $this->assertRenders(route($name), $name);
        }

        foreach (array_keys(config('help.articles')) as $slug) {
            $this->assertRenders(route('help.article', $slug), "helpartikel {$slug}");
        }
    }

    /** Elke URL uit de sitemap moet renderen — vangt verkeerde routenamen in marketingpagina's. */
    public function test_every_sitemap_page_renders(): void
    {
        $urls = $this->sitemapUrls();
        $this->assertGreaterThan(50, count($urls), 'Sitemap zonder URLs');

        foreach ($urls as $url) {
            $this->assertRenders((string) (parse_url($url, PHP_URL_PATH) ?: '/'), $url);
        }
    }

    public function test_all_app_pages_render_for_an_owner(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $routes = [
            'dashboard', 'invoices.index', 'invoices.create', 'quotes.index', 'quotes.create',
            'customers.index', 'customers.create', 'products.index', 'products.create',
            'purchases.index', 'purchases.create', 'purchases.inbox.index', 'purchases.recurring.index',
            'recurring.index', 'hours.index', 'trips.index', 'bank.index', 'incasso.index', 'aging.index',
            'stats.index', 'vat.index', 'yearreport.index', 'cashflow.index', 'export.index',
            'settings.company', 'settings.brand', 'settings.brands', 'settings.card', 'settings.site', 'settings.numbering', 'settings.reminders',
            'settings.emails', 'settings.emails.preview.thanks', 'settings.emails.preview.accept',
            'settings.security', 'settings.team', 'settings.integrations', 'administrations.index', 'billing.show',
        ];
        foreach ($routes as $name) {
            $this->assertRenders(route($name), $name);
        }

        // Pagina's met een record uit de demo-administratie.
        $sent = Invoice::regular()->where('status', 'sent')->firstOrFail();
        $this->assertRenders(route('invoices.show', $sent), 'factuur tonen');
        $this->assertRenders(route('invoices.pdf', $sent), 'factuur-pdf');
        $this->assertRenders(route('invoices.ubl', $sent), 'factuur-ubl');

        $draft = Invoice::where('status', 'draft')->first();
        if ($draft) {
            $this->assertRenders(route('invoices.edit', $draft), 'factuur bewerken');
        }

        $quote = Quote::firstOrFail();
        $this->assertRenders(route('quotes.show', $quote), 'offerte tonen');
        $this->assertRenders(route('quotes.pdf', $quote), 'offerte-pdf');

        $customer = Customer::firstOrFail();
        $this->assertRenders(route('customers.show', $customer), 'klantpagina');
        $this->assertRenders(route('customers.edit', $customer), 'klant bewerken');

        $purchase = PurchaseInvoice::first();
        if ($purchase) {
            $this->assertRenders(route('purchases.show', $purchase), 'inkoopfactuur');
            $this->assertRenders(route('purchases.edit', $purchase), 'inkoopfactuur bewerken');
        }

        $this->assertRenders(route('vat.pdf', ['year' => now()->year]), 'btw-pdf');
        $this->assertRenders(route('yearreport.pdf', ['year' => now()->year]), 'jaaroverzicht-pdf');
    }

    public function test_test_mails_render_without_errors(): void
    {
        // Alle mailsjablonen (factuur, herinnering, bedankt, offerte, akkoord, dagoverzicht, code).
        $this->artisan('mail:test', ['email' => 'rookproef@example.com'])->assertExitCode(0);
    }
}
