<?php

namespace Tests\Feature;

use App\Mail\BrandEvidenceMail;
use App\Models\BrandDossier;
use App\Models\BrandIncident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Merkbewaking: verwarringsincidenten vastleggen en het maandelijkse merkgebruik-dossier. */
class BrandEvidenceTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_contact_form_checkbox_logs_a_confusion_incident(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), [
            'name' => 'Piet Test', 'email' => 'piet@example.com', 'subject' => 'Uitbetaling',
            'message' => 'Waar blijft mijn uitbetaling van augustus?', 'confusion' => '1',
        ])->assertRedirect();

        $incident = BrandIncident::firstOrFail();
        $this->assertSame('contactformulier', $incident->source);
        $this->assertSame('piet@example.com', $incident->email);
        $this->assertStringContainsString('uitbetaling', $incident->summary);
    }

    public function test_public_confusion_page_logs_an_incident(): void
    {
        $this->get(route('confusion'))->assertOk();

        $this->post(route('confusion.send'), ['looking_for' => 'De affiliate-omgeving', 'how' => 'Google'])
            ->assertRedirect(route('confusion'));

        $incident = BrandIncident::where('source', 'verwarringspagina')->firstOrFail();
        $this->assertStringContainsString('Google', $incident->evidence);
    }

    public function test_owner_sees_the_page_and_can_log_and_export(): void
    {
        $user = $this->demoUser();  // eerste gebruiker in een verse database = id 1 = eigenaar
        $this->actingAs($user);

        $this->get(route('brand.index'))->assertOk();

        $this->post(route('brand.incidents.store'), [
            'occurred_on' => now()->toDateString(), 'source' => 'telefoon', 'name' => 'Beller',
            'summary' => 'Vroeg naar affiliates.', 'evidence' => 'Noemde het andere bedrijf.',
        ])->assertRedirect();
        $this->assertSame(1, BrandIncident::count());

        $this->get(route('brand.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_monthly_dossier_is_built_and_mailed(): void
    {
        Mail::fake();
        $this->demoUser();

        $this->artisan('brand:evidence', ['--month' => now()->format('Y-m')])->assertExitCode(0);

        $dossier = BrandDossier::where('month', now()->format('Y-m'))->firstOrFail();
        $this->assertNotEmpty($dossier->manifest);
        $this->assertContains('gebruiksrapport.txt', array_column($dossier->manifest, 'file'));
        $this->assertContains('factuurexport.csv', array_column($dossier->manifest, 'file'));
        Mail::assertSent(BrandEvidenceMail::class);
    }
}
