<?php

namespace Tests\Feature;

use App\Models\VatFiling;
use App\Services\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

class VatFilingTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_settings_filing_status_and_payment_reference(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $company = $user->company;

        $this->get(route('vat.index'))->assertOk();

        $this->patch(route('vat.settings'), [
            'vat_period' => 'quarter',
            'ob_number' => '0360.00.012.B.02',
            'vat_reminder_enabled' => true,
        ])->assertRedirect();

        $company->refresh();
        $this->assertSame('036000012B02', $company->ob_number);
        $this->assertSame('quarter', $company->vat_period);

        $this->patch(route('vat.filing.update', ['year' => 2023, 'type' => 'quarter', 'period' => 3]), [
            'filed' => true,
            'manual' => ['2a' => ['base' => 1000, 'vat' => 210]],
        ])->assertRedirect();

        $filing = VatFiling::where('year', 2023)->where('period', 3)->firstOrFail();
        $this->assertNotNull($filing->filed_at);
        $this->assertEquals(210.0, (float) $filing->manual['2a']['vat']);

        // Officiële voorbeeldwaarde uit de Belastingdienst-specificatie.
        $period = app(VatService::class)->overview($company->fresh(), 2023)['periods'][2];
        $this->assertSame('0036000011302270', $period['payment']['reference']);
        $this->assertTrue($period['filed']);
        $this->assertEquals(210.0, (float) collect($period['rubrieken'])->firstWhere('key', '2a')['vat']);
    }

    public function test_unknown_period_type_is_rejected(): void
    {
        $this->actingAs($this->demoUser());
        $this->patch(route('vat.filing.update', ['year' => 2026, 'type' => 'quarter', 'period' => 9]), ['filed' => true])
            ->assertNotFound();
    }
}
