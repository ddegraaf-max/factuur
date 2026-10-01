<?php

namespace Tests\Feature;

use App\Models\Subcontractor;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Uit de pool halen (1.75.3): een bedrijf met prijsaanvragen in de geschiedenis
 * wordt gearchiveerd in plaats van geweigerd; het verdwijnt uit de keuzelijsten,
 * blijft bij oude uitvragen staan en is terug te zetten.
 */
class TenderArchiveTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_removing_a_company_with_history_archives_it_and_it_can_be_restored(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $package = WorkPackage::where('name', 'Metselwerk')->firstOrFail();
        $used = Subcontractor::create(['name' => 'Gebruikt BV', 'email' => 'a@metsel.test']);
        $fresh = Subcontractor::create(['name' => 'Nieuw BV', 'email' => 'b@metsel.test']);
        $used->workPackages()->sync([$package->id]);
        $fresh->workPackages()->sync([$package->id]);
        $this->post(route('tenders.store'), [
            'title' => 'Gevel', 'work_package_id' => $package->id, 'subcontractor_ids' => [$used->id], 'deadline' => now()->addDays(7)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();

        // Zonder geschiedenis: echt weg. Met geschiedenis: uit de pool, maar de aanvraag blijft.
        $this->delete(route('tenders.subcontractors.destroy', $fresh))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull(Subcontractor::find($fresh->id));
        $this->delete(route('tenders.subcontractors.destroy', $used))->assertRedirect()->assertSessionHasNoErrors();
        $used->refresh();
        $this->assertTrue($used->isArchived());
        $this->assertSame(1, $round->requests()->where('subcontractor_id', $used->id)->count());

        // Niet meer in de pool-lijst, wel onder "uit de pool gehaald"; niet meer in de kiezers.
        $this->get(route('tenders.pool'))->assertOk()->assertInertia(fn ($page) => $page->has('subcontractors', 0)->has('archived', 1)->where('archived.0.name', 'Gebruikt BV'));
        $this->get(route('tenders.index'))->assertOk();
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page->has('candidates', 0)->has('requests', 1));
        $this->post(route('tenders.store'), [
            'title' => 'Gevel 2', 'work_package_id' => $package->id, 'subcontractor_ids' => [$used->id], 'deadline' => now()->addDays(7)->toDateString(),
        ])->assertRedirect()->assertSessionHasErrors();

        // Terugzetten: weer gewoon kiesbaar.
        $this->patch(route('tenders.subcontractors.restore', $used))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($used->fresh()->isArchived());
        $this->get(route('tenders.pool'))->assertOk()->assertInertia(fn ($page) => $page->has('subcontractors', 1)->has('archived', 0));
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page->has('candidates', 0), 'Al aangeschreven, dus geen kandidaat');
    }
}
