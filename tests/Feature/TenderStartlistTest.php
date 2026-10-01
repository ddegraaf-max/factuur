<?php

namespace Tests\Feature;

use App\Models\Subcontractor;
use App\Models\WorkPackage;
use App\Services\TenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Startlijsten (1.75.2): kant-en-klare bedrijven per werkpakket, alleen voor
 * de eigenaar van het platform; één klik maakt het werkpakket en vult de pool.
 */
class TenderStartlistTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_the_owner_puts_a_starter_list_in_the_pool_once(): void
    {
        config(['services.marketing_stats.emails' => '']);
        $other = $this->demoUser();
        $owner = $this->demoUser();
        $owner->company->forceFill(['is_exempt' => true])->save();

        // Een gewone administratie ziet de lijsten niet en mag ze niet gebruiken.
        $this->actingAs($other)->get(route('tenders.pool'))->assertOk()->assertInertia(fn ($page) => $page->has('startlists', 0));
        $this->actingAs($other)->post(route('tenders.subcontractors.startlist'), ['key' => 'vloerverwarming'])->assertForbidden();

        // De eigenaar ziet de lijst, met aantallen.
        $this->actingAs($owner->fresh());
        $lists = app(TenderService::class)->startlists();
        $this->assertNotEmpty($lists, 'resources/data/startlijsten bevat minstens één lijst');
        $list = collect($lists)->firstWhere('key', 'vloerverwarming');
        $this->assertNotNull($list);
        $this->assertSame('Vloerverwarming', $list['package']);
        $this->assertGreaterThan(10, $list['count']);
        $this->get(route('tenders.pool'))->assertOk()->assertInertia(fn ($page) => $page->has('startlists', count($lists)));

        // Eén klik: werkpakket erbij, bedrijven in de pool met pakket, bron 'startlist'.
        $this->post(route('tenders.subcontractors.startlist'), ['key' => 'vloerverwarming'])->assertRedirect()->assertSessionHasNoErrors();
        $package = WorkPackage::where('name', 'Vloerverwarming')->firstOrFail();
        $this->assertSame($list['count'], Subcontractor::where('source', 'startlist')->count());
        $this->assertSame($list['count'], $package->subcontractors()->count());
        $this->assertSame($list['with_email'], Subcontractor::where('source', 'startlist')->whereNotNull('email')->count());
        $this->assertSame(0, Subcontractor::withoutGlobalScope('company')->where('company_id', $other->company_id)->count(), 'Niet bij een andere administratie');

        // Nog eens: niets dubbel, het werkpakket blijft één.
        $this->post(route('tenders.subcontractors.startlist'), ['key' => 'vloerverwarming'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($list['count'], Subcontractor::count());
        $this->assertSame(1, WorkPackage::where('name', 'Vloerverwarming')->count());
        $this->post(route('tenders.subcontractors.startlist'), ['key' => 'bestaat-niet'])->assertRedirect()->assertSessionHasErrors('subcontractor');

        // De plaklijst werkt nog net als eerst, via dezelfde importroutine.
        $this->post(route('tenders.subcontractors.import'), ['lines' => "Nieuw Bedrijf; nieuw@voorbeeld.test; 0612345678; Aalsmeer; Vloerverwarming\n" . Subcontractor::first()->name . "; ; ; ; Vloerverwarming"])
            ->assertRedirect()->assertSessionHasNoErrors();
        $new = Subcontractor::where('name', 'Nieuw Bedrijf')->firstOrFail();
        $this->assertSame(['nieuw@voorbeeld.test', 'import', [$package->id]], [$new->email, $new->source, $new->workPackages()->pluck('work_packages.id')->all()]);
        $this->assertSame($list['count'] + 1, Subcontractor::count(), 'De bestaande naam is overgeslagen');
    }
}
