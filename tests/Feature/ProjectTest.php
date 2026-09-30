<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use App\Models\Subcontractor;
use App\Models\TenderRound;
use App\Models\TimeEntry;
use App\Models\WorkPackage;
use App\Services\InvoiceManager;
use App\Services\ProjectService;
use App\Services\QuoteManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Projecten (1.73.0): offertes, facturen, inkoopfacturen, uren, ritten en
 * uitvragen per klus bij elkaar, met een voorcalculatie en het resultaat.
 */
class ProjectTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_a_project_gets_a_number_and_gathers_its_documents(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $customer = Customer::orderBy('id')->firstOrFail();

        $this->post(route('projects.store'), [
            'name' => 'Uitbouw Dorpsstraat 12', 'customer_id' => $customer->id, 'location' => 'Woerden',
            'starts_on' => '2026-10-05', 'ends_on' => '2026-11-20',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $project = Project::firstOrFail();
        $this->assertSame('P-0001', $project->number);
        $this->assertSame($customer->id, $project->customer_id);

        // Tweede project: volgend nummer.
        $this->post(route('projects.store'), ['name' => 'Dakkapel'])->assertRedirect();
        $this->assertSame('P-0002', Project::orderByDesc('id')->first()->number);

        // Een offerte met het project; de factuur eruit hoort er dan vanzelf bij, net als een uitvraag.
        $quote = Quote::where('status', 'accepted')->firstOrFail();
        $quote->update(['project_id' => $project->id]);
        $invoice = app(QuoteManager::class)->convertToInvoice($quote->fresh());
        $this->assertSame($project->id, $invoice->project_id);
        // Een concept telt nog niet als omzet; pas na verzenden.
        $invoice = app(InvoiceManager::class)->send($invoice);

        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $package = WorkPackage::where('name', 'Metselwerk')->firstOrFail();
        $sub = Subcontractor::create(['name' => 'Metselbedrijf Test', 'email' => 'info@metsel.test', 'city' => 'Woerden']);
        $sub->workPackages()->sync([$package->id]);
        $this->post(route('tenders.from_quote', $quote), [
            'work_package_id' => $package->id, 'subcontractor_ids' => [$sub->id], 'deadline' => now()->addDays(7)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();
        $this->assertSame($project->id, $round->project_id);
        $request = $round->requests()->firstOrFail();
        $request->update(['status' => 'responded', 'price' => 4000, 'responded_at' => now()]);
        $this->post(route('tenders.award', [$round, $request]))->assertRedirect();

        // Inkoop met het project, en uren.
        $this->post(route('purchases.store'), [
            'supplier_name' => 'Bouwmaat', 'category' => 'Inkoop goederen', 'project_id' => $project->id,
            'invoice_date' => '2026-10-06', 'vat_lines' => [['base' => 1000, 'rate' => 21, 'vat' => 210]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($project->id, PurchaseInvoice::firstOrFail()->project_id);
        $this->post(route('hours.store'), [
            'customer_id' => $customer->id, 'project_id' => $project->id, 'description' => 'Metselen', 'work_date' => '2026-10-07',
            'minutes' => 480, 'hourly_rate' => 50, 'billable' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($project->id, TimeEntry::orderByDesc('id')->first()->project_id);

        // De cijfers: afgesproken uit de offerte, gefactureerd, kosten en het gegunde werk als verplichting.
        $figures = app(ProjectService::class)->figures($project->fresh());
        $this->assertEqualsWithDelta((float) $quote->subtotal, $figures['agreed'], 0.01);
        $this->assertEqualsWithDelta((float) $invoice->subtotal, $figures['invoiced'], 0.01);
        $this->assertEqualsWithDelta(1000.0, $figures['actual']['material'], 0.01);
        $this->assertEqualsWithDelta(400.0, $figures['actual']['labour'], 0.01, '8 uur tegen 50');
        $this->assertEqualsWithDelta(1400.0, $figures['costs'], 0.01);
        $this->assertEqualsWithDelta(4000.0, $figures['awarded'], 0.01);
        $this->assertEqualsWithDelta(4000.0, $figures['awarded_open'], 0.01, 'Nog geen inkoopfactuur van de metselaar');
        $this->assertEqualsWithDelta($figures['invoiced'] - 1400.0, $figures['result'], 0.01);
        $this->assertEqualsWithDelta($figures['agreed'] - 5400.0, $figures['expected'], 0.01);
        $this->assertSame(1, $figures['counts']['rounds']);

        // Komt de factuur van de metselaar binnen op het project, dan telt de gunning niet nog eens mee.
        $this->post(route('purchases.store'), [
            'supplier_name' => 'Metselbedrijf Test', 'category' => 'Uitbesteed werk', 'project_id' => $project->id,
            'invoice_date' => '2026-11-01', 'vat_lines' => [['base' => 4000, 'rate' => 21, 'vat' => 840]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $figures = app(ProjectService::class)->figures($project->fresh());
        $this->assertEqualsWithDelta(4000.0, $figures['actual']['subcontract'], 0.01);
        $this->assertEqualsWithDelta(0.0, $figures['awarded_open'], 0.01);
        $this->assertEqualsWithDelta(5400.0, $figures['expected_costs'], 0.01);

        // De pagina's.
        $this->get(route('projects.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Projects/Index')
            ->has('projects', 2)->where('projects.1.number', 'P-0001')->where('projects.1.costs', fn ($v) => abs((float) $v - 5400.0) < 0.01));
        $this->get(route('projects.show', $project))->assertOk()->assertInertia(fn ($page) => $page->component('Projects/Show')
            ->where('project.number', 'P-0001')->has('quotes', 1)->has('invoices', 1)->has('purchases', 2)->has('hours', 1)->has('rounds', 1)
            ->where('figures.costs', fn ($v) => abs((float) $v - 5400.0) < 0.01));
    }

    public function test_budget_lines_linking_and_closing(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $project = Project::create(['name' => 'Verbouwing']);

        $this->put(route('projects.budget', $project), ['lines' => [
            ['kind' => 'material', 'description' => 'Stenen en mortel', 'amount' => 2500],
            ['kind' => 'labour', 'description' => '40 uur', 'amount' => 2000],
            ['kind' => 'subcontract', 'description' => '', 'amount' => 0],
        ]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $project->budgetLines()->count(), 'Een lege regel wordt niet bewaard');
        $figures = app(ProjectService::class)->figures($project->fresh());
        $this->assertEqualsWithDelta(4500.0, $figures['budget_total'], 0.01);
        $this->assertEqualsWithDelta(2500.0, $figures['budget']['material'], 0.01);

        // Koppelen en losmaken vanaf de projectpagina.
        $invoices = Invoice::where('status', 'paid')->orderBy('id')->limit(2)->get();
        $this->post(route('projects.link', $project), ['type' => 'invoice', 'ids' => $invoices->pluck('id')->all()])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $project->invoices()->count());
        $this->post(route('projects.unlink', $project), ['type' => 'invoice', 'id' => $invoices->first()->id])->assertRedirect();
        $this->assertSame(1, $project->invoices()->count());
        $this->assertNull($invoices->first()->fresh()->project_id);

        // Een factuur van een andere administratie koppelt niet mee.
        $other = $this->demoUser();
        $foreign = Invoice::withoutGlobalScope('company')->where('company_id', $other->company_id)->firstOrFail();
        $this->actingAs($user);
        $this->post(route('projects.link', $project), ['type' => 'invoice', 'ids' => [$foreign->id]])->assertRedirect();
        $this->assertNull($foreign->fresh()->project_id);

        // Sluiten: uit de keuzelijst, maar nog te zien; verwijderen laat de documenten staan.
        $this->patch(route('projects.status', $project), ['status' => 'closed'])->assertRedirect();
        $this->assertSame('closed', $project->fresh()->status);
        $this->assertSame([], app(ProjectService::class)->options($user->company));
        $this->assertCount(1, app(ProjectService::class)->options($user->company, $project->id), 'Het eigen project blijft kiesbaar op het formulier');
        $kept = $project->invoices()->first();
        $this->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));
        $this->assertNull(Project::find($project->id));
        $this->assertNotNull($kept->fresh());
        $this->assertNull($kept->fresh()->project_id);
    }

    public function test_the_forms_offer_the_open_projects_and_keep_the_choice(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $customer = Customer::orderBy('id')->firstOrFail();
        $project = Project::create(['name' => 'Kozijnen', 'customer_id' => $customer->id]);

        $this->get(route('invoices.create', ['project' => $project->id]))->assertOk()->assertInertia(fn ($page) => $page
            ->has('projects', 1)->where('projects.0.label', 'P-0001 · Kozijnen')->where('preselect_project_id', $project->id));
        $this->get(route('quotes.create'))->assertOk()->assertInertia(fn ($page) => $page->has('projects', 1));
        $this->get(route('purchases.create'))->assertOk()->assertInertia(fn ($page) => $page->has('projects', 1));
        $this->get(route('hours.index'))->assertOk()->assertInertia(fn ($page) => $page->has('project_options', 1));

        $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'payment_terms' => 14, 'project_id' => $project->id,
            'lines' => [['description' => 'Kozijnen', 'quantity' => 1, 'unit_price' => 3000, 'vat_rate' => 21]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $invoice = Invoice::orderByDesc('id')->firstOrFail();
        $this->assertSame($project->id, $invoice->project_id);

        // Bewerken zonder het veld laat het project staan; met leeg veld gaat het eraf.
        $this->put(route('invoices.update', $invoice), [
            'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(), 'payment_terms' => 14, 'project_id' => null,
            'lines' => [['description' => 'Kozijnen', 'quantity' => 1, 'unit_price' => 3000, 'vat_rate' => 21]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($invoice->fresh()->project_id);
    }
}
