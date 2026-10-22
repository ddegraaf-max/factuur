<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Het beperkte portaal van Fachora (config/brand.php 'portal'): alleen wat een
 * Pools bedrijf gebruikt. Verborgen onderdelen geven 404, /dashboard gaat naar
 * de opdrachten, er is geen proefperiode, en de aanbieder is Creditline B.V.
 */
class FachoraPortalTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    private function polishUser()
    {
        $user = $this->demoUser();
        $user->company->forceFill(['country' => 'PL', 'currency' => 'PLN', 'vat_number' => '5260250883'])->save();

        return $user;
    }

    public function test_hidden_parts_of_the_app_do_not_exist_under_fachora(): void
    {
        config(['brand.active' => 'fachora']);
        $this->actingAs($this->polishUser());

        foreach (['hours.index', 'trips.index', 'purchases.index', 'tenders.index', 'bank.index', 'direct-debit.index', 'incasso.index',
            'stats.index', 'export.index', 'import.index', 'billing.show', 'settings.site', 'settings.card', 'settings.sms', 'settings.integrations', 'settings.activity'] as $name) {
            $this->get(route($name))->assertNotFound();
        }
    }

    public function test_hidden_parts_give_404_even_before_logging_in(): void
    {
        config(['brand.active' => 'fachora']);

        $this->get(route('hours.index'))->assertNotFound();
        $this->get(route('billing.show'))->assertNotFound();
        // Een bestaand onderdeel stuurt een bezoeker zonder account gewoon naar het inlogscherm.
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
    }

    public function test_the_kept_parts_still_render_under_fachora(): void
    {
        config(['brand.active' => 'fachora']);
        $this->actingAs($this->polishUser());

        foreach (['invoices.index', 'quotes.index', 'customers.index', 'products.index', 'vat.index',
            'settings.company', 'settings.brand', 'settings.numbering', 'settings.emails', 'settings.team', 'settings.security'] as $name) {
            $this->assertRenders(route($name), $name);
        }
    }

    public function test_the_dashboard_sends_a_fachora_company_to_its_jobs(): void
    {
        config(['brand.active' => 'fachora']);
        $this->actingAs($this->polishUser());

        $this->get(route('dashboard'))->assertRedirect(route('fachora.dashboard'));
    }

    public function test_there_is_no_trial_under_fachora_but_there_is_elsewhere(): void
    {
        $user = $this->polishUser();
        $user->company->forceFill(['trial_ends_at' => now()->subDay(), 'subscription_status' => null])->save();

        config(['brand.active' => 'fachora']);
        $this->actingAs($user)->get(route('invoices.index'))->assertOk();

        config(['brand.active' => 'easyinvoice']);
        $this->actingAs($user)->get(route('invoices.index'))->assertRedirect(route('billing.show'));
    }

    public function test_the_default_brand_keeps_everything(): void
    {
        $this->actingAs($this->demoUser());

        $this->get(route('hours.index'))->assertOk();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_the_client_gets_the_portal_menu_only_under_fachora(): void
    {
        config(['brand.active' => 'fachora']);
        $this->assertContains('invoices.index', \App\Support\Brand::forClient()['portal']['menu']);
        $this->assertTrue(\App\Support\Brand::forClient()['portal']['free']);

        config(['brand.active' => 'lopra_pl']);
        $this->assertNull(\App\Support\Brand::forClient()['portal']);
    }

    public function test_fachora_names_creditline_bv_as_the_operator(): void
    {
        config(['brand.active' => 'fachora']);

        foreach (['/o-nas', '/kontakt', '/regulamin', '/polityka-prywatnosci', '/faq', '/fachora-protect', '/dla-firm', '/'] as $path) {
            foreach (['pl', 'en'] as $lang) {
                $html = (string) $this->get($path . '?lang=' . $lang)->assertOk()->getContent();
                $this->assertStringContainsString('Creditline B.V.', $html, "{$path} ({$lang}) noemt Creditline B.V. niet");
                $this->assertStringNotContainsStringIgnoringCase('Budomatch', $html, "{$path} ({$lang}) noemt Budomatch nog");
                $this->assertStringNotContainsString('7010869430', $html, "{$path} ({$lang}) noemt het oude NIP nog");
            }
        }
    }
}
