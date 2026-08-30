<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Rookproef voor Lopra Polska: elke app-pagina moet ook onder het merk
 * lopra_pl (markt pl: Poolse taal, PLN, btw 23/8/5/0, NIP) renderen. Vangt
 * fouten in marktbewuste code (Market::…, money(), vertaalsleutels) vóór livegang.
 */
class SmokePagesPlTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_all_app_pages_render_for_a_polish_owner(): void
    {
        config(['brand.active' => 'lopra_pl']);

        $user = $this->demoUser();
        $user->company->forceFill(['country' => 'PL', 'currency' => 'PLN', 'vat_number' => '5260250883'])->save();
        $this->actingAs($user);

        $routes = [
            'dashboard', 'invoices.index', 'invoices.create', 'quotes.index', 'quotes.create',
            'customers.index', 'customers.create', 'products.index', 'products.create',
            'purchases.index', 'purchases.create', 'purchases.inbox.index', 'purchases.recurring.index',
            'recurring.index', 'hours.index', 'trips.index', 'bank.index', 'incasso.index', 'aging.index',
            'vat.index', 'reports.cashflow', 'reports.year', 'export.index', 'import.index',
            'settings.company', 'settings.brand', 'settings.numbering', 'settings.reminders', 'settings.email-texts',
            'settings.team', 'settings.security', 'settings.integrations', 'settings.card', 'settings.site',
            'settings.activity', 'billing.show', 'search',
        ];

        foreach ($routes as $name) {
            if (! \Illuminate\Support\Facades\Route::has($name)) {
                continue; // routenamen die (nog) niet bestaan, slaan we over
            }
            $this->assertRenders(route($name), $name);
        }

        // Factuur- en offertedetail, PDF en de Poolse windykacja-acties.
        $invoice = \App\Models\Invoice::withoutGlobalScopes()->where('company_id', $user->company_id)->whereNotNull('number')->first();
        if ($invoice) {
            $this->assertRenders(route('invoices.show', $invoice), 'invoices.show');
            $this->assertRenders(route('invoices.edit', $invoice), 'invoices.edit');
            $this->actingAs($user)->get(route('windykacja.claim', $invoice))->assertOk();
        }

        // De Inertia-payload draagt de Poolse markt.
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('data-locale="pl"', false)
            ->assertSee('&quot;currency&quot;:&quot;PLN&quot;', false);
    }

    public function test_polish_public_pages_and_documents(): void
    {
        config(['brand.active' => 'lopra_pl']);

        foreach (['/', '/demo', '/login', '/register', '/status', '/faq', '/kontakt', '/kalkulator-odsetek'] as $path) {
            $this->assertRenders($path);
        }
    }
}
