<?php

namespace Tests\Feature;

use App\Support\OwnerAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Eigenaarsrechten (marketing-inzichten, administraties): nooit voor een
 * demogebruiker, en in productie nooit "gewoon de eerste gebruiker".
 */
class OwnerAccessTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_a_demo_user_is_never_the_owner_even_when_first(): void
    {
        config(['services.marketing_stats.emails' => '']);

        // Verse omgeving: de allereerste gebruiker is de demo-sandbox (is_demo aan).
        $demo = $this->demoUser(realCompany: false);
        $this->assertTrue((bool) $demo->company->is_demo);

        $this->assertFalse(OwnerAccess::allows($demo));
        $this->assertNotSame($demo->id, OwnerAccess::owner()?->id);

        // Het eigenaarsmenu en de eigenaarspagina's blijven dicht.
        $this->actingAs($demo)->get(route('dashboard'))->assertOk()
            ->assertSee('&quot;platform&quot;:false', false);
        $this->actingAs($demo)->get(route('owner.companies.index'))->assertForbidden();
    }

    public function test_without_configured_emails_the_exempt_administration_is_the_owner(): void
    {
        config(['services.marketing_stats.emails' => '']);

        $first = $this->demoUser();
        $owner = $this->demoUser();
        $owner->company->forceFill(['is_exempt' => true])->save();

        $this->assertSame($owner->id, OwnerAccess::owner()?->id);
        $this->assertTrue(OwnerAccess::allows($owner->fresh()));
        $this->assertFalse(OwnerAccess::allows($first->fresh()));
    }

    public function test_in_production_there_is_no_first_user_fallback(): void
    {
        config(['services.marketing_stats.emails' => '']);
        $first = $this->demoUser();   // gewone, niet-vrijgestelde klant

        $this->app['env'] = 'production';
        try {
            $this->assertNull(OwnerAccess::owner());
            $this->assertFalse(OwnerAccess::allows($first));
            $this->assertSame([], OwnerAccess::emails());
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_configured_emails_win(): void
    {
        $user = $this->demoUser();
        config(['services.marketing_stats.emails' => ' ' . mb_strtoupper($user->email) . ' ,ander@example.com']);

        $this->assertTrue(OwnerAccess::allows($user));
        $this->assertSame($user->id, OwnerAccess::owner()?->id);
        $this->assertSame([mb_strtoupper($user->email), 'ander@example.com'], OwnerAccess::emails());
    }
}
