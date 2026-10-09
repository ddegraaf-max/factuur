<?php

namespace Tests\Feature;

use App\Mail\VatReminderMail;
use App\Services\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Btw-aangifte in EasyInvoice vanaf een datum (1.77.0). Wie zijn btw tot een
 * moment nog ergens anders doet, wil tot die tijd geen aangifte-melding, geen
 * btw-kaart en geen herinnering. Daarna gewoon weer alles.
 */
class VatFilingFromTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_periods_before_the_start_date_ask_no_attention(): void
    {
        $user = $this->demoUser();
        $company = $user->company;
        $vat = app(VatService::class);

        // Zonder datum: het lopende tijdvak staat op de kaart.
        $this->assertNotNull($vat->attention($company)['current']);

        $company->update(['vat_filing_from' => now()->addYear()->startOfYear()->toDateString()]);
        $attention = $vat->attention($company->fresh());

        $this->assertNull($attention['current'], 'het lopende tijdvak eindigt vóór de startdatum');
        $this->assertNull($attention['due'], 'en er staat dus ook geen aangifte open');

        // Dashboard: geen gele balk, de btw-kaart noemt alleen de datum.
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('vat_due', null)
            ->where('kpis.vat_from', fn ($v) => is_string($v) && $v !== ''));

        // Geen herinneringsmail, ook al staat die aan.
        Mail::fake();
        $company->update(['vat_reminder_enabled' => true]);
        $this->artisan('vat:remind')->assertExitCode(0);
        Mail::assertNotSent(VatReminderMail::class);
    }

    public function test_the_start_date_is_saved_and_cleared_from_the_vat_settings(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $this->patch(route('vat.settings'), ['vat_period' => 'quarter', 'vat_filing_from' => '2027-01-01'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('2027-01-01', $user->company->fresh()->vat_filing_from->toDateString());

        $this->get(route('vat.index'))->assertOk()->assertInertia(fn ($page) => $page->where('settings.vat_filing_from', '2027-01-01'));

        $this->patch(route('vat.settings'), ['vat_period' => 'quarter', 'vat_filing_from' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($user->company->fresh()->vat_filing_from);

        $this->patch(route('vat.settings'), ['vat_period' => 'quarter', 'vat_filing_from' => 'gisteren'])
            ->assertSessionHasErrors('vat_filing_from');
    }
}
