<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Project;
use App\Models\PurchaseInvoice;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * 1.102.1: niet elke inkoopfactuur hoort bij een project (verzekering, incasso, kantoor).
 * Gemarkeerd als algemene kosten verdwijnt hij uit "Inkoopfacturen koppelen"; op de factuur
 * zelf is het een vinkje, en toch koppelen maakt de markering ongedaan.
 */
class PurchaseProjectExemptTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function project(): Project
    {
        $customer = Customer::orderBy('id')->firstOrFail();
        $this->post(route('projects.store'), ['name' => 'Uitbouw Dorpsstraat 12', 'customer_id' => $customer->id])->assertRedirect()->assertSessionHasNoErrors();

        return Project::where('name', 'Uitbouw Dorpsstraat 12')->firstOrFail();
    }

    private function purchase(string $supplier, array $extra = []): PurchaseInvoice
    {
        $this->post(route('purchases.store'), $extra + [
            'supplier_name' => $supplier, 'category' => 'Uitbesteed werk',
            'invoice_date' => '2026-06-25', 'vat_lines' => [['base' => 700, 'rate' => 21, 'vat' => 147]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        return PurchaseInvoice::where('supplier_name', $supplier)->firstOrFail();
    }

    private function offered(Project $project, PurchaseInvoice $purchase): bool
    {
        $candidates = app(ProjectService::class)->candidates(auth()->user()->company, $project);

        return collect($candidates['purchases'])->contains('id', $purchase->id);
    }

    public function test_general_costs_disappear_from_the_link_list_with_one_click_and_can_come_back(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $project = $this->project();
        $armaere = $this->purchase('Armaere Incassospecialisten');
        $this->assertTrue($this->offered($project, $armaere));
        $this->assertFalse($armaere->project_exempt);

        // Eén klik in de koppellijst: weg, geteld als verborgen, en in het logboek.
        $this->from(route('projects.show', $project))->post(route('purchases.exempt', $armaere), ['exempt' => true])
            ->assertRedirect(route('projects.show', $project))->assertSessionHas('flash');
        $armaere->refresh();
        $this->assertTrue($armaere->project_exempt);
        $this->assertNull($armaere->project_id);
        $this->assertFalse($this->offered($project, $armaere));
        $this->get(route('projects.show', $project))->assertOk()->assertInertia(fn ($page) => $page
            ->where('candidates.purchases_hidden', 1)
            ->where('candidates.purchases', fn ($list) => ! collect($list)->contains('id', $armaere->id)));
        $this->assertTrue(ActivityLog::withoutGlobalScope('company')->where('action', 'updated')->where('subject_id', $armaere->id)->where('description', 'like', '%algemene kosten%')->exists());
        $this->get(route('purchases.show', $armaere))->assertOk()->assertInertia(fn ($page) => $page->where('purchase.project_exempt', true));

        // Terugdraaien.
        $this->post(route('purchases.exempt', $armaere), ['exempt' => false])->assertRedirect();
        $this->assertFalse($armaere->fresh()->project_exempt);
        $this->assertTrue($this->offered($project, $armaere));
    }

    public function test_the_checkbox_on_the_purchase_invoice_and_linking_anyway(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $project = $this->project();

        // Nieuw ingevoerd met het vinkje: niet aangeboden.
        $insurance = $this->purchase('Nationale Verzekeraar', ['project_exempt' => true, 'category' => 'Verzekeringen']);
        $this->assertTrue($insurance->project_exempt);
        $this->assertFalse($this->offered($project, $insurance));

        // Vinkje én een project tegelijk: het project wint.
        $this->patch(route('purchases.update', $insurance), [
            'supplier_name' => 'Nationale Verzekeraar', 'category' => 'Verzekeringen', 'project_id' => $project->id, 'project_exempt' => true,
            'invoice_date' => '2026-06-25', 'vat_lines' => [['base' => 700, 'rate' => 21, 'vat' => 147]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $insurance->refresh();
        $this->assertSame($project->id, $insurance->project_id);
        $this->assertFalse($insurance->project_exempt);

        // Gemarkeerd en daarna toch via het project gekoppeld: de markering vervalt.
        $this->post(route('purchases.exempt', $insurance), ['exempt' => true])->assertRedirect();
        $this->assertNull($insurance->fresh()->project_id);
        $this->post(route('projects.link', $project), ['type' => 'purchase', 'ids' => [$insurance->id]])->assertRedirect()->assertSessionHasNoErrors();
        $insurance->refresh();
        $this->assertSame($project->id, $insurance->project_id);
        $this->assertFalse($insurance->project_exempt);
    }
}
