<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectPlanItem;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use App\Models\TimeEntry;
use App\Models\Trip;
use App\Services\ProjectPlanService;
use App\Services\ProjectService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projecten: per klus de offertes, facturen, inkoop, uren, ritten en
 * uitvragen bij elkaar, met een voorcalculatie en het resultaat.
 */
class ProjectController extends Controller
{
    public function __construct(private ProjectService $service, private ProjectPlanService $plan) {}

    public function index(Request $request): Response
    {
        $status = in_array($request->input('status'), ['open', 'closed', 'all'], true) ? $request->input('status') : 'open';
        $q = trim((string) $request->input('q'));

        $projects = Project::with('customer')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('number', 'like', "%{$q}%")->orWhere('location', 'like', "%{$q}%")))
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Projects/Index', [
            'status' => $status,
            'q' => $q,
            'counts' => [
                'open' => Project::where('status', 'open')->count(),
                'closed' => Project::where('status', 'closed')->count(),
            ],
            'projects' => $projects->map(function (Project $project) {
                $figures = $this->service->figures($project);

                return [
                    'id' => $project->id,
                    'number' => $project->number,
                    'name' => $project->name,
                    'status' => $project->status,
                    'customer_name' => $project->customer?->name,
                    'location' => $project->location,
                    'agreed' => $figures['agreed'],
                    'invoiced' => $figures['invoiced'],
                    'costs' => $figures['costs'],
                    'result' => $figures['result'],
                    'margin' => $figures['margin'],
                    'expected' => $figures['expected'],
                ];
            })->values(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Projects/Form', [
            'project' => null,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'preset_customer_id' => $request->integer('customer') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $project = Project::create($data);
        Audit::log('created', $project, __('Project :number aangemaakt: :name', ['number' => $project->number, 'name' => $project->name]), [], $project->company_id);

        return redirect()->route('projects.show', $project)->with('flash', __('Project :number aangemaakt.', ['number' => $project->number]));
    }

    public function show(Project $project): Response
    {
        $project->load('customer', 'budgetLines');
        // Gegund werk dat nog niet op de tijdslijn staat, komt erbij.
        $this->plan->sync($project);
        $figures = $this->service->figures($project);
        $day = fn ($d) => $d?->translatedFormat('j M Y');

        return Inertia::render('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'number' => $project->number,
                'name' => $project->name,
                'status' => $project->status,
                'location' => $project->location,
                'description' => $project->description,
                'starts_on_label' => $day($project->starts_on),
                'ends_on_label' => $day($project->ends_on),
                'agreed_price' => $project->agreed_price !== null ? (float) $project->agreed_price : null,
                'customer_id' => $project->customer_id,
                'customer_name' => $project->customer?->name,
                'created_at_label' => $day($project->created_at),
                'auto_earlier' => (bool) $project->auto_earlier,
            ],
            'plan' => $this->plan->timeline($project),
            'subcontractors' => $this->plan->subcontractorOptions(auth()->user()->company),
            'figures' => $figures,
            'budget_lines' => $project->budgetLines->map(fn ($l) => [
                'id' => $l->id, 'kind' => $l->kind, 'description' => $l->description, 'amount' => (float) $l->amount,
            ])->values(),
            'quotes' => $project->quotes()->orderByDesc('quote_date')->get()->map(fn (Quote $q) => [
                'id' => $q->id, 'number' => $q->number ?: __('— concept —'), 'status' => $q->status, 'status_label' => $q->status_label,
                'date_label' => $day($q->quote_date), 'subtotal' => (float) $q->subtotal, 'total' => (float) $q->total,
            ])->values(),
            'invoices' => $project->invoices()->orderByDesc('invoice_date')->get()->map(fn (Invoice $i) => [
                'id' => $i->id, 'number' => $i->number ?: __('— concept —'), 'status' => $i->status, 'is_credit' => (bool) $i->is_credit,
                'date_label' => $day($i->invoice_date), 'subtotal' => (float) $i->subtotal, 'total' => (float) $i->total,
                'remaining' => round((float) $i->total - (float) $i->paid_total, 2),
            ])->values(),
            'purchases' => $project->purchases()->orderByDesc('invoice_date')->get()->map(fn (PurchaseInvoice $p) => [
                'id' => $p->id, 'supplier' => $p->supplier_name, 'reference' => $p->supplier_reference, 'category' => $p->category ? __($p->category) : null,
                'status' => $p->status, 'date_label' => $day($p->invoice_date), 'subtotal' => (float) $p->subtotal, 'total' => (float) $p->total,
            ])->values(),
            'hours' => $project->timeEntries()->with(['customer', 'company'])->orderByDesc('work_date')->limit(100)->get()->map(fn (TimeEntry $e) => [
                'id' => $e->id, 'date_label' => $day($e->work_date), 'description' => $e->description, 'minutes' => (int) $e->minutes,
                'amount' => $e->amount(), 'invoiced' => (bool) $e->invoice_id,
            ])->values(),
            'trips' => $project->trips()->with(['customer', 'company'])->orderByDesc('trip_date')->limit(100)->get()->map(fn (Trip $t) => [
                'id' => $t->id, 'date_label' => $day($t->trip_date), 'route' => trim($t->from_location . ' → ' . $t->to_location), 'kilometers' => (float) $t->kilometers,
                'amount' => $t->amount(), 'invoiced' => (bool) $t->invoice_id,
            ])->values(),
            'rounds' => $this->service->awardedRequests($project),
            'candidates' => $this->service->candidates(auth()->user()->company, $project),
            'kinds' => Project::KINDS,
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Projects/Form', [
            'project' => [
                'id' => $project->id,
                'number' => $project->number,
                'name' => $project->name,
                'status' => $project->status,
                'customer_id' => $project->customer_id,
                'location' => $project->location,
                'starts_on' => $project->starts_on?->toDateString(),
                'ends_on' => $project->ends_on?->toDateString(),
                'description' => $project->description,
                'agreed_price' => $project->agreed_price !== null ? (float) $project->agreed_price : null,
            ],
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'preset_customer_id' => null,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validated($request));

        return redirect()->route('projects.show', $project)->with('flash', __('Project bijgewerkt.'));
    }

    /** Sluiten of heropenen. */
    public function status(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(Project::STATUSES)]]);
        $project->update(['status' => $data['status']]);

        return back()->with('flash', $data['status'] === 'closed' ? __('Project gesloten.') : __('Project heropend.'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        // De documenten blijven bestaan; alleen de koppeling verdwijnt (nullOnDelete).
        $number = $project->number;
        $project->delete();

        return redirect()->route('projects.index')->with('flash', __('Project :number verwijderd. De documenten zelf zijn er nog.', ['number' => $number]));
    }

    /** De voorcalculatie opslaan. */
    public function budget(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'lines' => ['nullable', 'array', 'max:60'],
            'lines.*.kind' => ['required', Rule::in(Project::KINDS)],
            'lines.*.description' => ['nullable', 'string', 'max:200'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:-1000000', 'max:100000000'],
        ]);
        $this->service->saveBudget($project, $data['lines'] ?? []);

        return back()->with('flash', __('Calculatie opgeslagen.'));
    }

    /** Documenten koppelen aan dit project. */
    public function link(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['quote', 'invoice', 'purchase', 'hours', 'trip', 'round'])],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ]);
        $n = $this->service->link(auth()->user()->company, $project, $data['type'], $data['ids']);

        return back()->with('flash', __(':n gekoppeld aan :number.', ['n' => $n, 'number' => $project->number]));
    }

    /** Eén document losmaken. */
    public function unlink(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['quote', 'invoice', 'purchase', 'hours', 'trip', 'round'])],
            'id' => ['required', 'integer'],
        ]);
        $this->service->link(auth()->user()->company, null, $data['type'], [$data['id']]);

        return back()->with('flash', __('Losgemaakt van :number.', ['number' => $project->number]));
    }

    /* ------------------------------------------------------------ planning */

    /** Eigen onderdeel toevoegen of een onderdeel aanpassen. */
    public function planSave(Request $request, Project $project, ?ProjectPlanItem $item = null): RedirectResponse
    {
        $this->planItem($project, $item);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'subcontractor_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['title.required' => __('Geef het onderdeel een naam.')]);
        try {
            $this->plan->save($project, $data, $item);
        } catch (\DomainException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back()->with('flash', __('Planning opgeslagen.'));
    }

    public function planDestroy(Project $project, ProjectPlanItem $item): RedirectResponse
    {
        $this->planItem($project, $item);
        $this->plan->delete($item);

        return back()->with('flash', __('Onderdeel van de planning gehaald.'));
    }

    /** Gepland, bezig of klaar; "klaar" vóór de einddatum vraagt de volgende partij(en) automatisch om eerder te beginnen. */
    public function planStatus(Request $request, Project $project, ProjectPlanItem $item): RedirectResponse
    {
        $this->planItem($project, $item);
        $data = $request->validate([
            'status' => ['required', Rule::in(ProjectPlanItem::STATUSES)],
            'done_on' => ['nullable', 'date'],
        ]);
        $sent = $this->plan->setStatus($item, $data['status'], filled($data['done_on'] ?? null) ? Carbon::parse($data['done_on']) : null);
        $flash = match ($data['status']) {
            'done' => $sent > 0
                ? trans_choice(':title is klaar. Eén partij is gevraagd of ze eerder kan beginnen.|:title is klaar. :count partijen zijn gevraagd of ze eerder kunnen beginnen.', $sent, ['title' => $item->title, 'count' => $sent])
                : __(':title is klaar.', ['title' => $item->title]),
            'started' => __(':title is gestart.', ['title' => $item->title]),
            default => __(':title staat weer gepland.', ['title' => $item->title]),
        };

        return back()->with('flash', $flash);
    }

    /** Zelf vragen of een partij eerder kan beginnen. */
    public function planEarlier(Request $request, Project $project, ProjectPlanItem $item): RedirectResponse
    {
        $this->planItem($project, $item);
        $data = $request->validate(['start' => ['required', 'date'], 'reason' => ['nullable', 'string', 'max:500']]);
        try {
            $ok = $this->plan->askEarlier($item, Carbon::parse($data['start']), filled($data['reason'] ?? null) ? trim($data['reason']) : null);
        } catch (\DomainException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back()->with('flash', $ok ? __('Gevraagd aan :name of het eerder kan.', ['name' => $item->subcontractor?->name]) : __('De mail kon niet worden verstuurd.'));
    }

    /** Automatisch om eerder vragen aan of uit. */
    public function planAuto(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['auto_earlier' => ['required', 'boolean']]);
        $project->update(['auto_earlier' => $data['auto_earlier']]);

        return back()->with('flash', $data['auto_earlier'] ? __('Automatisch om eerder vragen staat aan.') : __('Automatisch om eerder vragen staat uit.'));
    }

    /** Alleen onderdelen van dit project, en alleen zolang het open is. */
    private function planItem(Project $project, ?ProjectPlanItem $item): void
    {
        if ($item && (int) $item->project_id !== (int) $project->id) {
            abort(404);
        }
        if (! $project->isOpen()) {
            abort(403, __('Dit project is gesloten.'));
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'customer_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(Project::STATUSES)],
            'location' => ['nullable', 'string', 'max:160'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string', 'max:5000'],
            'agreed_price' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ], [
            'name.required' => __('Geef het project een naam.'),
            'ends_on.after_or_equal' => __('De einddatum ligt vóór de startdatum.'),
        ]);

        // Alleen een klant van deze administratie (de scope regelt dat).
        $data['customer_id'] = ! empty($data['customer_id']) ? Customer::whereKey($data['customer_id'])->value('id') : null;
        foreach (['location', 'starts_on', 'ends_on', 'description', 'agreed_price'] as $field) {
            $data[$field] = filled($data[$field] ?? null) ? $data[$field] : null;
        }
        $data['status'] = $data['status'] ?? 'open';

        return $data;
    }
}
