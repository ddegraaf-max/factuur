<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\TimeEntry;
use App\Services\InvoiceManager;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Urenregistratie: gewerkte uren bijhouden per klant of project (handmatig of
 * met de timer) en openstaande uren met één klik omzetten naar een
 * conceptfactuur via de bestaande InvoiceManager.
 */
class TimeEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $request->input('period', 'month');
        $status = $request->input('status', 'open');
        $customerId = $request->input('customer_id');

        [$from, $to] = match ($period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'all' => [null, null],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $entries = TimeEntry::with(['customer:id,name,hourly_rate', 'user:id,name', 'invoice:id,number,status'])
            ->whereNull('timer_started_at')
            ->when($from, fn ($q) => $q->whereBetween('work_date', [$from, $to]))
            ->when($status === 'open', fn ($q) => $q->whereNull('invoice_id'))
            ->when($status === 'invoiced', fn ($q) => $q->whereNotNull('invoice_id'))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $company = $request->user()->company;

        $entries->getCollection()->transform(fn ($e) => [
            'id' => $e->id,
            'work_date' => $e->work_date->format('Y-m-d'),
            'work_date_label' => $e->work_date->translatedFormat('D j M'),
            'customer_id' => $e->customer_id,
            'customer_name' => $e->customer?->name,
            'project' => $e->project,
            'description' => $e->description,
            'minutes' => $e->minutes,
            'hourly_rate' => $e->hourly_rate !== null ? (float) $e->hourly_rate : null,
            'effective_rate' => $e->effectiveRate(),
            'amount' => ($e->billable && ! $e->time_card_id) ? $e->amount() : null,
            'billable' => $e->billable,
            'time_card_id' => $e->time_card_id,
            'user_name' => $e->user?->name,
            'invoice_id' => $e->invoice_id,
            'invoice_number' => $e->invoice?->number,
        ]);

        // Openstaande factureerbare uren per klant — de "één klik factureren"-lijst.
        $billable = TimeEntry::billable()->with('customer:id,name,hourly_rate')->get()
            ->groupBy('customer_id')
            ->map(function ($group) {
                $first = $group->first();
                $amounts = $group->map(fn ($e) => $e->amount());

                return [
                    'customer_id' => $first->customer_id,
                    'customer_name' => $first->customer?->name ?? __('Onbekend'),
                    'entries' => $group->count(),
                    'minutes' => $group->sum('minutes'),
                    'amount' => $amounts->contains(null) ? null : round($amounts->sum(), 2),
                ];
            })
            ->sortByDesc('minutes')
            ->values();

        // Lopende timer van de ingelogde gebruiker.
        $timer = TimeEntry::whereNotNull('timer_started_at')
            ->where('user_id', $request->user()->id)
            ->latest('timer_started_at')
            ->first();

        $weekMinutes = (int) TimeEntry::whereNull('timer_started_at')
            ->whereBetween('work_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('minutes');
        $monthMinutes = (int) TimeEntry::whereNull('timer_started_at')
            ->whereBetween('work_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('minutes');

        return Inertia::render('Uren/Index', [
            'entries' => $entries,
            'filters' => ['period' => $period, 'status' => $status, 'customer_id' => $customerId ? (int) $customerId : null],
            'stats' => [
                'week_minutes' => $weekMinutes,
                'month_minutes' => $monthMinutes,
                'open_minutes' => (int) TimeEntry::billable()->sum('minutes'),
            ],
            'billable_by_customer' => $billable,
            'timer' => $timer ? [
                'id' => $timer->id,
                'started_at' => $timer->timer_started_at->toIso8601String(),
                'customer_name' => $timer->customer?->name,
                'description' => $timer->description,
            ] : null,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'hourly_rate']),
            'projects' => TimeEntry::whereNotNull('project')->select('project')->distinct()->orderBy('project')->limit(100)->pluck('project'),
            'default_hourly_rate' => $company->default_hourly_rate !== null ? (float) $company->default_hourly_rate : null,
            // Strippenkaarten: tegoeden per klant (voor het beheerblok en de
            // "wordt afgeschreven van..."-hint bij het schrijven).
            'time_cards' => \App\Models\TimeCard::with('customer:id,name', 'invoice:id,number,status')
                ->withSum('entries', 'minutes')
                ->orderBy('id')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'customer_id' => $c->customer_id,
                    'customer_name' => $c->customer?->name,
                    'name' => $c->name,
                    'total_minutes' => $c->total_minutes,
                    'used_minutes' => (int) ($c->entries_sum_minutes ?? 0),
                    'remaining_minutes' => max(0, $c->total_minutes - (int) ($c->entries_sum_minutes ?? 0)),
                    'price' => (float) $c->price,
                    'valid_until' => $c->valid_until?->format('Y-m-d'),
                    'valid_until_label' => $c->valid_until?->translatedFormat('j M Y'),
                    'expired' => $c->valid_until ? $c->valid_until->isPast() : false,
                    'invoice_id' => $c->invoice_id,
                    'invoice_number' => $c->invoice?->number,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $entry = TimeEntry::create($this->validated($request));

        // Strippenkaart: automatisch afschrijven zolang het tegoed toereikend is.
        \App\Models\TimeCard::apply($entry);

        return back()->with('flash', $entry->fresh()->time_card_id
            ? __('Uren geschreven en afgeschreven van de strippenkaart.')
            : __('Uren geschreven.'));
    }

    public function update(Request $request, TimeEntry $entry): RedirectResponse
    {
        if ($entry->invoice_id) {
            return back()->with('error', __('Deze uren staan al op een factuur en kunnen niet meer worden gewijzigd.'));
        }

        $entry->update($this->validated($request));

        // Dekking herbeoordelen: past de regel nog op de kaart (of juist nu wel)?
        \App\Models\TimeCard::apply($entry->fresh());

        return back()->with('flash', __('Uren bijgewerkt.'));
    }

    public function destroy(TimeEntry $entry): RedirectResponse
    {
        if ($entry->invoice_id) {
            return back()->with('error', __('Deze uren staan al op een factuur en kunnen niet worden verwijderd.'));
        }

        $entry->delete();

        return back()->with('flash', __('Urenregel verwijderd.'));
    }

    /** Start de timer; een eventueel nog lopende timer wordt eerst netjes gestopt. */
    public function timerStart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('company_id', $request->user()->company_id)],
            'project' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->stopRunningTimer($request);

        TimeEntry::create([
            'customer_id' => $data['customer_id'] ?? null,
            'project' => $data['project'] ?? null,
            'description' => $data['description'] ?? __('Gewerkte uren'),
            'work_date' => today(),
            'minutes' => 0,
            'timer_started_at' => now(),
        ]);

        return back()->with('flash', __('Timer gestart.'));
    }

    public function timerStop(Request $request): RedirectResponse
    {
        $stopped = $this->stopRunningTimer($request);

        return $stopped
            ? back()->with('flash', __('Timer gestopt — de uren staan in de lijst.'))
            : back()->with('error', __('Er loopt geen timer.'));
    }

    /**
     * Zet de openstaande uren van een klant met één klik om naar een
     * conceptfactuur (één factuurregel per urenregel, oudste eerst).
     */
    public function invoice(Request $request, InvoiceManager $manager): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('company_id', $request->user()->company_id)],
            'vat_rate' => ['nullable', 'numeric', 'in:' . implode(',', \App\Support\Market::vatRates())],
        ]);

        $entries = TimeEntry::billable()
            ->where('customer_id', $data['customer_id'])
            ->with('customer:id,name,hourly_rate')
            ->orderBy('work_date')
            ->orderBy('id')
            ->get();

        if ($entries->isEmpty()) {
            return back()->with('error', __('Er staan geen factureerbare uren open voor deze klant.'));
        }

        $missingRate = $entries->first(fn ($e) => $e->effectiveRate() === null);
        if ($missingRate) {
            return back()->with('error', __('Er is geen uurtarief bekend voor deze uren. Stel een tarief in bij de klant, bij Instellingen → Bedrijfsgegevens, of op de urenregel zelf.'));
        }

        $lines = $entries->map(fn ($e) => [
            'description' => $e->description,
            'details' => trim($e->work_date->translatedFormat('j F Y') . ($e->project ? " · {$e->project}" : '')),
            'quantity' => round($e->minutes / 60, 2),
            'unit' => __('uur'),
            'unit_price' => $e->effectiveRate(),
            'vat_rate' => (float) ($data['vat_rate'] ?? \App\Support\Market::defaultVatRate()),
        ])->all();

        $invoice = $manager->create([
            'customer_id' => (int) $data['customer_id'],
            'lines' => $lines,
        ]);

        TimeEntry::whereIn('id', $entries->pluck('id'))->update(['invoice_id' => $invoice->id]);

        $hours = round($entries->sum('minutes') / 60, 2);

        return redirect()->route('invoices.edit', $invoice)
            ->with('flash', __("Conceptfactuur aangemaakt met :hours uur — controleer en verstuur 'm.", ['hours' => $hours]));
    }

    /* ===================== Helpers ===================== */

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('company_id', $request->user()->company_id)],
            'project' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'work_date' => ['required', 'date'],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'billable' => ['nullable', 'boolean'],
        ], [
            'description.required' => __('Vul een omschrijving in — die komt straks op de factuur.'),
            'minutes.required' => __('Vul de gewerkte tijd in.'),
            'minutes.min' => __('Vul de gewerkte tijd in.'),
            'minutes.max' => __('Eén urenregel kan maximaal 24 uur zijn.'),
        ]);

        return [
            'customer_id' => $data['customer_id'] ?? null,
            'project' => filled($data['project'] ?? null) ? trim($data['project']) : null,
            'description' => trim($data['description']),
            'work_date' => $data['work_date'],
            'minutes' => (int) $data['minutes'],
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'billable' => (bool) ($data['billable'] ?? true),
        ];
    }

    /** Stop de lopende timer van deze gebruiker (minimaal 1 minuut). */
    protected function stopRunningTimer(Request $request): ?TimeEntry
    {
        $timer = TimeEntry::whereNotNull('timer_started_at')
            ->where('user_id', $request->user()->id)
            ->latest('timer_started_at')
            ->first();

        if (! $timer) {
            return null;
        }

        $minutes = max(1, (int) round($timer->timer_started_at->diffInSeconds(now()) / 60));

        $timer->update([
            'minutes' => min($minutes, 1440),
            'timer_started_at' => null,
        ]);

        // Ook timeruren tellen af van een eventuele strippenkaart.
        \App\Models\TimeCard::apply($timer->fresh());

        return $timer;
    }
}
