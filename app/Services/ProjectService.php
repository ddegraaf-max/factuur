<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectBudgetLine;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use App\Models\TenderRequest;
use App\Models\TenderRound;
use App\Models\TimeEntry;
use App\Models\Trip;

/**
 * De cijfers van een project: wat is afgesproken (offertes), wat is
 * gefactureerd en ontvangen, wat is uitgegeven (inkoopfacturen, uren, ritten),
 * wat is gegund aan onderaannemers, en hoe verhoudt dat zich tot de
 * voorcalculatie per kostensoort. Alles exclusief btw, behalve wat is ontvangen.
 */
class ProjectService
{
    /** Inkoopcategorieën die bij een kostensoort horen; de rest is 'overig'. */
    private const PURCHASE_KINDS = [
        'Inkoop goederen' => 'material',
        'Uitbesteed werk' => 'subcontract',
    ];

    /** Open projecten voor een keuzelijst, met het nummer erbij. */
    public function options(Company $company, ?int $includeId = null): array
    {
        return Project::withoutGlobalScope('company')->where('company_id', $company->id)
            ->where(fn ($q) => $q->where('status', 'open')->when($includeId, fn ($q) => $q->orWhere('id', $includeId)))
            ->orderBy('number')
            ->get(['id', 'number', 'name', 'customer_id', 'status'])
            ->map(fn (Project $p) => ['id' => $p->id, 'label' => $p->label(), 'customer_id' => $p->customer_id, 'status' => $p->status])
            ->values()
            ->all();
    }

    /**
     * Alle cijfers van één project.
     *
     * @return array<string, mixed>
     */
    public function figures(Project $project): array
    {
        $quotes = $project->quotes()->whereNotIn('status', ['draft', 'rejected', 'expired'])->get();
        $invoices = $project->invoices()->whereNotIn('status', ['draft', 'cancelled'])->get();
        $purchases = $project->purchases()->get();
        $hours = $project->timeEntries()->with(['customer', 'company'])->get();
        $trips = $project->trips()->with(['customer', 'company'])->get();
        $rounds = $project->tenderRounds()->with('awardedRequest.subcontractor')->get();
        $budget = $project->budgetLines;

        $signed = fn (Invoice $i) => ($i->is_credit ? -1 : 1) * (float) $i->subtotal;
        $agreedQuotes = round((float) $quotes->where('status', 'accepted')->sum('subtotal'), 2);
        $agreed = $project->agreed_price !== null ? (float) $project->agreed_price : $agreedQuotes;
        $invoiced = round($invoices->sum($signed), 2);
        $received = round($invoices->where('is_credit', false)->sum(fn (Invoice $i) => (float) $i->paid_total), 2);
        $outstanding = round($invoices->where('is_credit', false)->sum(fn (Invoice $i) => max(0, (float) $i->total - (float) $i->paid_total)), 2);

        // Werkelijke kosten per kostensoort.
        $actual = array_fill_keys(Project::KINDS, 0.0);
        foreach ($purchases as $purchase) {
            $actual[self::PURCHASE_KINDS[$purchase->category] ?? 'other'] += (float) $purchase->subtotal;
        }
        $hoursMinutes = (int) $hours->sum('minutes');
        $hoursAmount = round($hours->sum(fn (TimeEntry $e) => $e->amount() ?? 0), 2);
        $actual['labour'] += $hoursAmount;
        $tripsKm = round((float) $trips->sum('kilometers'), 1);
        $tripsAmount = round($trips->sum(fn (Trip $t) => $t->amount()), 2);
        $actual['other'] += $tripsAmount;
        foreach ($actual as $kind => $amount) {
            $actual[$kind] = round($amount, 2);
        }

        // Gegund aan onderaannemers: een verplichting die nog niet als inkoopfactuur
        // binnen hoeft te zijn. Is er al een inkoopfactuur van dat bedrijf op dit
        // project, dan telt de gunning niet nog eens mee.
        $awarded = 0.0;
        $awardedOpen = 0.0;
        $supplierNames = $purchases->pluck('supplier_name')->map(fn ($n) => mb_strtolower(trim((string) $n)))->filter()->all();
        foreach ($rounds as $round) {
            $request = $round->awardedRequest;
            if (! $request || ! $request->hasPrice()) {
                continue;
            }
            $price = (float) $request->price;
            $awarded += $price;
            $name = mb_strtolower(trim((string) $request->subcontractor?->name));
            $invoicedBySupplier = $name !== '' && collect($supplierNames)->contains(fn ($s) => $s === $name || str_contains($s, $name) || str_contains($name, $s));
            if (! $invoicedBySupplier) {
                $awardedOpen += $price;
            }
        }

        $costs = round(array_sum($actual), 2);
        $expectedCosts = round($costs + $awardedOpen, 2);
        $result = round($invoiced - $costs, 2);
        $expected = round($agreed - $expectedCosts, 2);

        $budgetByKind = array_fill_keys(Project::KINDS, 0.0);
        foreach ($budget as $line) {
            $budgetByKind[$line->kind] = round($budgetByKind[$line->kind] + (float) $line->amount, 2);
        }
        $budgetTotal = round(array_sum($budgetByKind), 2);

        return [
            'agreed' => $agreed,
            'agreed_from_quotes' => $agreedQuotes,
            'invoiced' => $invoiced,
            'received' => $received,
            'outstanding' => $outstanding,
            'costs' => $costs,
            'actual' => $actual,
            'awarded' => round($awarded, 2),
            'awarded_open' => round($awardedOpen, 2),
            'expected_costs' => $expectedCosts,
            'result' => $result,
            'margin' => $invoiced > 0 ? round($result / $invoiced * 100, 1) : null,
            'expected' => $expected,
            'expected_margin' => $agreed > 0 ? round($expected / $agreed * 100, 1) : null,
            'budget' => $budgetByKind,
            'budget_total' => $budgetTotal,
            'hours_minutes' => $hoursMinutes,
            'hours_amount' => $hoursAmount,
            'trips_km' => $tripsKm,
            'trips_amount' => $tripsAmount,
            'counts' => [
                'quotes' => $quotes->count(),
                'invoices' => $invoices->count(),
                'purchases' => $purchases->count(),
                'hours' => $hours->count(),
                'trips' => $trips->count(),
                'rounds' => $rounds->count(),
            ],
        ];
    }

    /**
     * Koppelt documenten aan een project (of maakt ze los met null). Alleen
     * documenten van dezelfde administratie; de rest wordt stil overgeslagen.
     *
     * @param  array<int, int>  $ids
     */
    public function link(Company $company, ?Project $project, string $type, array $ids): int
    {
        $query = match ($type) {
            'quote' => Quote::withoutGlobalScope('company'),
            'invoice' => Invoice::withoutGlobalScope('company'),
            'purchase' => PurchaseInvoice::withoutGlobalScope('company'),
            'hours' => TimeEntry::withoutGlobalScope('company'),
            'trip' => Trip::withoutGlobalScope('company'),
            'round' => TenderRound::withoutGlobalScope('company'),
            default => throw new \DomainException(__('Onbekend soort document.')),
        };

        return $query->where('company_id', $company->id)->whereIn('id', $ids)->update(['project_id' => $project?->id]);
    }

    /**
     * Wat er nog te koppelen valt: documenten zonder project, van deze klant
     * eerst. Kort gehouden; het gaat om de laatste maanden.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function candidates(Company $company, Project $project): array
    {
        $order = fn ($q, string $date) => $q->where('company_id', $company->id)->whereNull('project_id')
            ->when($project->customer_id, fn ($q) => $q->orderByRaw('CASE WHEN customer_id = ? THEN 0 ELSE 1 END', [$project->customer_id]))
            ->orderByDesc($date)->limit(40);

        return [
            'quotes' => $order(Quote::withoutGlobalScope('company')->whereNotIn('status', ['rejected', 'expired']), 'quote_date')->get()
                ->map(fn (Quote $q) => ['id' => $q->id, 'label' => trim($q->number . ' ' . $q->customer_name), 'sub' => $q->quote_date?->translatedFormat('j M Y') . ' · ' . money((float) $q->subtotal), 'mine' => (int) $q->customer_id === (int) $project->customer_id])->values()->all(),
            'invoices' => $order(Invoice::withoutGlobalScope('company')->where('status', '!=', 'cancelled'), 'invoice_date')->get()
                ->map(fn (Invoice $i) => ['id' => $i->id, 'label' => trim(($i->number ?: __('concept')) . ' ' . $i->customer_name), 'sub' => $i->invoice_date?->translatedFormat('j M Y') . ' · ' . money((float) $i->subtotal), 'mine' => (int) $i->customer_id === (int) $project->customer_id])->values()->all(),
            'purchases' => PurchaseInvoice::withoutGlobalScope('company')->where('company_id', $company->id)->whereNull('project_id')->orderByDesc('invoice_date')->limit(40)->get()
                ->map(fn (PurchaseInvoice $p) => ['id' => $p->id, 'label' => trim($p->supplier_name . ' ' . ($p->supplier_reference ?? '')), 'sub' => $p->invoice_date?->translatedFormat('j M Y') . ' · ' . money((float) $p->subtotal) . ($p->category ? ' · ' . __($p->category) : ''), 'mine' => false])->values()->all(),
            'hours' => $order(TimeEntry::withoutGlobalScope('company'), 'work_date')->get()
                ->map(fn (TimeEntry $e) => ['id' => $e->id, 'label' => trim(($e->project ? $e->project . ' · ' : '') . (string) $e->description), 'sub' => $e->work_date?->translatedFormat('j M Y') . ' · ' . round($e->minutes / 60, 2) . ' ' . __('uur'), 'mine' => (int) $e->customer_id === (int) $project->customer_id])->values()->all(),
            'trips' => $order(Trip::withoutGlobalScope('company'), 'trip_date')->get()
                ->map(fn (Trip $t) => ['id' => $t->id, 'label' => trim($t->from_location . ' → ' . $t->to_location), 'sub' => $t->trip_date?->translatedFormat('j M Y') . ' · ' . (float) $t->kilometers . ' km', 'mine' => (int) $t->customer_id === (int) $project->customer_id])->values()->all(),
            'rounds' => TenderRound::withoutGlobalScope('company')->where('company_id', $company->id)->whereNull('project_id')->orderByDesc('id')->limit(40)->get()
                ->map(fn (TenderRound $r) => ['id' => $r->id, 'label' => $r->title, 'sub' => __($r->status) . ($r->location ? ' · ' . $r->location : ''), 'mine' => false])->values()->all(),
        ];
    }

    /**
     * De voorcalculatie in één keer opslaan: de regels zoals ze op het scherm staan.
     *
     * @param  array<int, array{kind: string, description: string, amount: float|int|string}>  $lines
     */
    public function saveBudget(Project $project, array $lines): void
    {
        $project->budgetLines()->delete();
        foreach (array_values($lines) as $i => $line) {
            if (trim((string) ($line['description'] ?? '')) === '' && (float) ($line['amount'] ?? 0) == 0.0) {
                continue;
            }
            ProjectBudgetLine::create([
                'project_id' => $project->id,
                'kind' => in_array($line['kind'] ?? null, Project::KINDS, true) ? $line['kind'] : 'other',
                'description' => mb_substr(trim((string) ($line['description'] ?? '')), 0, 200) ?: __('Zonder omschrijving'),
                'amount' => round((float) ($line['amount'] ?? 0), 2),
                'sort' => $i,
            ]);
        }
    }

    /** Het aantal gegunde aanvragen, voor de lijst. */
    public function awardedRequests(Project $project): array
    {
        return $project->tenderRounds()->with('awardedRequest.subcontractor')->get()
            ->map(fn (TenderRound $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'status' => $r->status,
                'awarded_to' => $r->awardedRequest?->subcontractor?->name,
                'price' => $r->awardedRequest instanceof TenderRequest && $r->awardedRequest->hasPrice() ? (float) $r->awardedRequest->price : null,
            ])->values()->all();
    }
}
