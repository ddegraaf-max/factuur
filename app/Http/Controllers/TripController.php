<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Trip;
use App\Services\InvoiceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kilometerregistratie: zakelijke ritten bijhouden en de kilometervergoeding
 * per klant met één klik omzetten naar een conceptfactuur (reiskostenregels),
 * of alleen bewaren als kilometeradministratie voor de eigen aangifte.
 */
class TripController extends Controller
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

        $trips = Trip::with(['customer:id,name', 'user:id,name', 'invoice:id,number,status'])
            ->when($from, fn ($q) => $q->whereBetween('trip_date', [$from, $to]))
            ->when($status === 'open', fn ($q) => $q->whereNull('invoice_id'))
            ->when($status === 'invoiced', fn ($q) => $q->whereNotNull('invoice_id'))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->orderByDesc('trip_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $trips->getCollection()->transform(fn ($t) => [
            'id' => $t->id,
            'trip_date' => $t->trip_date->format('Y-m-d'),
            'trip_date_label' => $t->trip_date->translatedFormat('D j M'),
            'customer_id' => $t->customer_id,
            'customer_name' => $t->customer?->name,
            'from_location' => $t->from_location,
            'to_location' => $t->to_location,
            'round_trip' => $t->round_trip,
            'description' => $t->description,
            'kilometers' => (float) $t->kilometers,
            'rate' => $t->rate !== null ? (float) $t->rate : null,
            'effective_rate' => $t->effectiveRate(),
            'amount' => $t->billable ? $t->amount() : null,
            'billable' => $t->billable,
            'user_name' => $t->user?->name,
            'invoice_id' => $t->invoice_id,
            'invoice_number' => $t->invoice?->number,
        ]);

        // Openstaande factureerbare ritten per klant — "één klik factureren".
        $billable = Trip::billable()->with('customer:id,name')->get()
            ->groupBy('customer_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'customer_id' => $first->customer_id,
                    'customer_name' => $first->customer?->name ?? __('Onbekend'),
                    'trips' => $group->count(),
                    'kilometers' => round($group->sum(fn ($t) => (float) $t->kilometers), 1),
                    'amount' => round($group->sum(fn ($t) => $t->amount()), 2),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $company = $request->user()->company;

        return Inertia::render('Ritten/Index', [
            'trips' => $trips,
            'filters' => ['period' => $period, 'status' => $status, 'customer_id' => $customerId ? (int) $customerId : null],
            'stats' => [
                'month_km' => round((float) Trip::whereBetween('trip_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('kilometers'), 1),
                'year_km' => round((float) Trip::whereBetween('trip_date', [now()->startOfYear(), now()->endOfYear()])->sum('kilometers'), 1),
                'open_amount' => round(Trip::billable()->get()->sum(fn ($t) => $t->amount()), 2),
            ],
            'billable_by_customer' => $billable,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'default_km_rate' => (float) ($company->default_km_rate ?? \App\Support\Market::get('km_rate', 0.23)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Trip::create($this->validated($request));

        return back()->with('flash', __('Rit geregistreerd.'));
    }

    public function update(Request $request, Trip $trip): RedirectResponse
    {
        if ($trip->invoice_id) {
            return back()->with('error', __('Deze rit staat al op een factuur en kan niet meer worden gewijzigd.'));
        }

        $trip->update($this->validated($request));

        return back()->with('flash', __('Rit bijgewerkt.'));
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        if ($trip->invoice_id) {
            return back()->with('error', __('Deze rit staat al op een factuur en kan niet worden verwijderd.'));
        }

        $trip->delete();

        return back()->with('flash', __('Rit verwijderd.'));
    }

    /**
     * Zet de openstaande factureerbare ritten van een klant met één klik om
     * naar een conceptfactuur (één reiskostenregel per rit, oudste eerst).
     */
    public function invoice(Request $request, InvoiceManager $manager): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('company_id', $request->user()->company_id)],
            'vat_rate' => ['nullable', 'numeric', 'in:' . implode(',', \App\Support\Market::vatRates())],
        ]);

        $trips = Trip::billable()
            ->where('customer_id', $data['customer_id'])
            ->orderBy('trip_date')
            ->orderBy('id')
            ->get();

        if ($trips->isEmpty()) {
            return back()->with('error', __('Er staan geen factureerbare ritten open voor deze klant.'));
        }

        $lines = $trips->map(fn ($t) => [
            'description' => $t->lineDescription(),
            'details' => trim($t->trip_date->translatedFormat('j F Y') . ($t->description ? " · {$t->description}" : '')),
            'quantity' => (float) $t->kilometers,
            'unit' => 'km',
            'unit_price' => $t->effectiveRate(),
            'vat_rate' => (float) ($data['vat_rate'] ?? \App\Support\Market::defaultVatRate()),
        ])->all();

        $invoice = $manager->create([
            'customer_id' => (int) $data['customer_id'],
            'lines' => $lines,
        ]);

        Trip::whereIn('id', $trips->pluck('id'))->update(['invoice_id' => $invoice->id]);

        $km = round($trips->sum(fn ($t) => (float) $t->kilometers), 1);

        return redirect()->route('invoices.edit', $invoice)
            ->with('flash', __("Conceptfactuur aangemaakt met :km km aan reiskosten — controleer en verstuur 'm.", ['km' => $km]));
    }

    /* ===================== Helpers ===================== */

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('company_id', $request->user()->company_id)],
            'trip_date' => ['required', 'date'],
            'from_location' => ['required', 'string', 'max:190'],
            'to_location' => ['required', 'string', 'max:190'],
            'round_trip' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'kilometers' => ['required', 'numeric', 'min:0.1', 'max:20000'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'billable' => ['nullable', 'boolean'],
        ], [
            'from_location.required' => __('Vul de vertreklocatie in.'),
            'to_location.required' => __('Vul de bestemming in.'),
            'kilometers.required' => __('Vul het aantal kilometers in.'),
            'kilometers.min' => __('Vul het aantal kilometers in.'),
        ]);

        return [
            'customer_id' => $data['customer_id'] ?? null,
            'trip_date' => $data['trip_date'],
            'from_location' => trim($data['from_location']),
            'to_location' => trim($data['to_location']),
            'round_trip' => (bool) ($data['round_trip'] ?? false),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'kilometers' => round((float) $data['kilometers'], 1),
            'rate' => $data['rate'] ?? null,
            'billable' => (bool) ($data['billable'] ?? true),
        ];
    }
}
