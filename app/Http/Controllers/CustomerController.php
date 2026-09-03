<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->input('q');
        $type = $request->input('type');

        $customers = Customer::query()
            ->withCount([
                'invoices',
                'quotes',
                // Offertes die nog bij de klant liggen (verstuurd, geen reactie).
                'quotes as open_quotes_count' => fn ($qb) => $qb->where('status', 'sent'),
            ])
            ->when($q, fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('city', 'like', "%{$q}%")
                  ->orWhere('kvk_number', 'like', "%{$q}%");
            }))
            ->when($type && $type !== 'all', fn ($qb) => $qb->where('type', $type))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        // Augment with calculated outstanding (avoids N+1 by fetching once)
        $customers->getCollection()->transform(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'initials' => $c->initials,
                'type' => $c->type,
                'kvk_number' => $c->kvk_number,
                'city' => $c->city,
                'email' => $c->email,
                'invoices_count' => $c->invoices_count,
                'quotes_count' => $c->quotes_count,
                'open_quotes_count' => $c->open_quotes_count,
                'outstanding' => (float) $c->outstanding_total,
            ];
        });

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $q, 'type' => $type],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Form', [
            'customer' => null,
            'kvk_enabled' => app(\App\Services\KvkService::class)->enabled(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $customer = Customer::create($data);
        return redirect()->route('customers.index')->with('flash', __('Klant :name aangemaakt.', ['name' => $customer->name]));
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Form', [
            'customer' => $customer,
            'kvk_enabled' => app(\App\Services\KvkService::class)->enabled(),
        ]);
    }

    /**
     * Klantpagina: gegevens, kerncijfers (openstaand, omzet, betaalgedrag),
     * facturen, offertes en nog te factureren uren — alles per klant bij elkaar.
     */
    public function show(Customer $customer): Response
    {
        $year = now()->year;

        $invoices = $customer->invoices()->orderByDesc('invoice_date')->orderByDesc('id')->get();
        $quotes = $customer->quotes()->orderByDesc('quote_date')->orderByDesc('id')->get();

        $real = $invoices->whereNotIn('status', ['draft', 'cancelled']);
        $open = $real->filter(fn ($i) => ! $i->is_credit && in_array($i->status, ['sent', 'partial', 'overdue', 'incasso'], true));
        $signed = fn ($i) => ($i->is_credit ? -1 : 1) * (float) $i->subtotal;

        // Betaalgedrag: hoeveel dagen na de factuurdatum wordt er gemiddeld betaald,
        // en hoe vaak binnen de termijn?
        $paid = $real->filter(fn ($i) => ! $i->is_credit && $i->status === 'paid' && $i->paid_at);
        $avgDays = $paid->isNotEmpty()
            ? (int) round($paid->avg(fn ($i) => max(0, $i->invoice_date->diffInDays($i->paid_at, false))))
            : null;
        $onTime = $paid->filter(fn ($i) => ! $i->due_date || $i->paid_at->lte($i->due_date->copy()->endOfDay()))->count();

        $openQuotes = $quotes->where('status', 'sent');
        $decided = $quotes->whereIn('status', ['accepted', 'rejected', 'expired']);

        // Uren die nog op een factuur moeten.
        $unbilled = \App\Models\TimeEntry::where('customer_id', $customer->id)
            ->whereNull('invoice_id')
            ->where('billable', true)
            ->get(['minutes', 'hourly_rate']);

        return Inertia::render('Customers/Show', [
            'customer' => array_merge($customer->toArray(), [
                'initials' => $customer->initials,
                'address' => collect([$customer->address_line, trim(($customer->postal_code ?? '') . ' ' . ($customer->city ?? ''))])->filter()->implode(', '),
                'created_at_label' => $customer->created_at?->translatedFormat('j F Y'),
            ]),
            'year' => $year,
            'stats' => [
                'open_total' => round($open->sum(fn ($i) => (float) $i->total - (float) $i->paid_total), 2),
                'open_count' => $open->count(),
                'overdue_count' => $open->filter(fn ($i) => $i->status === 'overdue' || $i->is_overdue)->count(),
                'revenue_year' => round($real->filter(fn ($i) => $i->invoice_date->year === $year)->sum($signed), 2),
                'revenue_total' => round($real->sum($signed), 2),
                'invoice_count' => $real->where('is_credit', false)->count(),
                'credit_count' => $real->where('is_credit', true)->count(),
                'first_invoice_label' => $real->last()?->invoice_date?->translatedFormat('M Y'),
                'avg_days_to_pay' => $avgDays,
                'paid_count' => $paid->count(),
                'on_time_count' => $onTime,
                'quotes_open_count' => $openQuotes->count(),
                'quotes_open_total' => round((float) $openQuotes->sum('total'), 2),
                'quotes_accepted_count' => $decided->where('status', 'accepted')->count(),
                'quotes_decided_count' => $decided->count(),
                'unbilled_minutes' => (int) $unbilled->sum('minutes'),
                'unbilled_value' => round($unbilled->sum(fn ($e) => $e->minutes / 60 * (float) ($e->hourly_rate ?? $customer->hourly_rate ?? 0)), 2),
            ],
            'invoices' => $invoices->take(50)->values()->map(fn ($i) => [
                'id' => $i->id,
                'number' => $i->number ?: __('— concept —'),
                'is_credit' => (bool) $i->is_credit,
                'invoice_date_label' => $i->invoice_date->translatedFormat('j M Y'),
                'due_date_label' => $i->due_date?->translatedFormat('j M Y'),
                'status' => $i->status,
                'days_overdue' => $i->days_overdue,
                'total' => (float) $i->total,
                'remaining' => round((float) $i->total - (float) $i->paid_total, 2),
            ]),
            'invoices_total' => $invoices->count(),
            'quotes' => $quotes->take(50)->values()->map(fn ($q) => [
                'id' => $q->id,
                'number' => $q->number ?: __('— concept —'),
                'quote_date_label' => $q->quote_date->translatedFormat('j M Y'),
                'valid_until_label' => $q->valid_until?->translatedFormat('j M Y'),
                'status' => $q->status,
                'status_label' => $q->status_label,
                'is_expired' => $q->is_expired,
                'days_left' => $q->status === 'sent' ? $q->days_left : null,
                'converted' => (bool) $q->converted_invoice_id,
                'total' => (float) $q->total,
            ]),
            'quotes_total' => $quotes->count(),
            'hours_url' => \Illuminate\Support\Facades\Route::has('hours.index') ? route('hours.index') : null,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request));
        return redirect()->route('customers.show', $customer)->with('flash', __('Klant bijgewerkt.'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->invoices()->exists()) {
            return back()->withErrors(['delete' => __('Kan klant niet verwijderen: er bestaan facturen.')]);
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('flash', __('Klant verwijderd.'));
    }

    protected function validated(Request $request): array
    {
        $data = $this->validatedRules($request);

        // Pools NIP: alleen de cijfers bewaren (klanten krijgen geen controlecijfer-check).
        if (\App\Support\Market::isPl() && filled($data['vat_number'] ?? null)) {
            $data['vat_number'] = preg_replace('/\D/', '', $data['vat_number']) ?: null;
        }

        // Automatische incasso: een machtiging krijgt automatisch een kenmerk en staat aan zodra er een IBAN is.
        if (filled($data['mandate_iban'] ?? null)) {
            $data['mandate_iban'] = \App\Support\Iban::normalize($data['mandate_iban']);
            $data['mandate_reference'] = filled($data['mandate_reference'] ?? null) ? strtoupper(trim($data['mandate_reference'])) : 'EI' . auth()->user()->company_id . '-' . strtoupper(\Illuminate\Support\Str::random(8));
            $data['mandate_type'] = $data['mandate_type'] ?? 'CORE';
            $data['mandate_status'] = $data['mandate_status'] ?? 'active';
        } elseif (array_key_exists('mandate_iban', $data)) {
            $data['mandate_status'] = null;
        }

        return $data;
    }

    protected function validatedRules(Request $request): array
    {
        /*
         * Het subdomein eerst gelijktrekken, dan pas toetsen. Wie
         * "Keizersgracht214" of " keizersgracht214 " intypt bedoelt hetzelfde;
         * daar een foutmelding voor teruggeven is streng zonder dat het iets
         * oplevert. Wat er daarna nog fout aan is, is echt fout.
         */
        if ($request->has('vvemaat_slug')) {
            $request->merge([
                'vvemaat_slug' => strtolower(trim((string) $request->input('vvemaat_slug'))) ?: null,
            ]);
        }

        return $request->validate([
            'mandate_reference' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9+?\/\-:().,\' ]+$/'],
            'mandate_iban' => ['nullable', 'string', 'max:40', function ($attr, $value, $fail) {
                if (filled($value) && ! \App\Support\Iban::valid($value)) $fail(__('Dit is geen geldig IBAN.'));
            }],
            'mandate_holder' => ['nullable', 'string', 'max:70'],
            'mandate_signed_on' => ['nullable', 'date'],
            'mandate_type' => ['nullable', 'in:CORE,B2B'],
            'mandate_status' => ['nullable', 'in:active,revoked'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:business,consumer'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'kvk_number' => ['nullable', 'string', 'max:20'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'peppol_id' => ['nullable', 'string', 'max:50', 'regex:/^\d{4}:[\w.\-]+$/'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
            'language' => ['nullable', 'in:nl,en,pl'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'notes' => ['nullable', 'string'],
            /*
             * Het subdomein van de VvE-omgeving die bij deze klant hoort.
             *
             * Aan dit veld hangt of een betaalde factuur wordt doorgegeven aan
             * VvEMaat, en dus of het bestuur van die vereniging zijn
             * administratie kan blijven bijwerken. Een typefout betekent dat de
             * melding naar een omgeving gaat die niet bestaat en de juiste op
             * slot blijft — vandaar dezelfde vorm als VvEMaat zelf toestaat:
             * kleine letters, cijfers en koppeltekens, en niet beginnen of
             * eindigen met een koppelteken.
             */
            'vvemaat_slug' => ['nullable', 'string', 'max:63',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/'],
        ]);
    }
}
